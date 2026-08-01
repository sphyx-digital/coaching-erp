<?php

namespace App\Services\Approvals;

use App\Models\Approval;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reusable approval engine. A request names the approver role and an optional
 * SLA; a decision (approve/reject) is guarded against self-approval and
 * double-decision, applies the effect via a registered handler, and is audited.
 */
class ApprovalService
{
    public function __construct(
        private AuditLogger $audit,
        private NotificationService $notifications,
    ) {}

    public function request(?Model $approvable, string $action, string $title, string $approverRole, ?int $amount = null, array $meta = [], ?int $slaMinutes = null): Approval
    {
        $slaMinutes ??= (int) client_setting('approval_sla_minutes', 1440); // 24h default

        $approval = Approval::create([
            'institute_id' => current_institute()?->id,
            'branch_id' => $meta['branch_id'] ?? null,
            'approvable_type' => $approvable?->getMorphClass(),
            'approvable_id' => $approvable?->getKey(),
            'action' => $action,
            'title' => $title,
            'approver_role' => $approverRole,
            'status' => 'pending',
            'amount' => $amount,
            'meta' => $meta,
            'requested_by' => Auth::id(),
            'requested_at' => now(),
            'due_at' => now()->addMinutes($slaMinutes),
        ]);

        $this->audit->log('approval.requested', $approval, after: ['action' => $action, 'approver_role' => $approverRole]);

        return $approval;
    }

    public function decide(Approval $approval, User $user, bool $approve, ?string $reason = null): Approval
    {
        if (! $approval->isPending()) {
            throw new DomainException('This request has already been decided.');
        }

        if (! $this->canDecide($user, $approval)) {
            throw new DomainException('You are not authorised to decide this request.');
        }

        if (client_setting('forbid_self_approval', true) && $approval->requested_by && (int) $approval->requested_by === $user->id) {
            throw new DomainException('You cannot approve your own request.');
        }

        return DB::transaction(function () use ($approval, $user, $approve, $reason) {
            $approval->update([
                'status' => $approve ? 'approved' : 'rejected',
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_reason' => $reason,
            ]);

            if ($handler = $this->handler($approval->action)) {
                $approve ? $handler->handleApproved($approval) : $handler->handleRejected($approval);
            }

            $this->audit->log($approve ? 'approval.approved' : 'approval.rejected', $approval, after: ['reason' => $reason]);

            return $approval->fresh();
        });
    }

    /** Escalate pending requests past their SLA to the next role. */
    public function escalateOverdue(string $escalateTo = 'Institute Admin'): int
    {
        $overdue = Approval::where('status', 'pending')
            ->whereNotNull('due_at')->where('due_at', '<', now())->whereNull('escalated_at')->get();

        foreach ($overdue as $approval) {
            $approval->update(['escalated_at' => now(), 'escalated_to' => $escalateTo]);
            $this->notifications->dispatch('in_app', [
                'user_id' => $approval->requested_by,
                'subject' => 'Approval overdue',
                'body' => $approval->title.' is awaiting a decision.',
                'institute_id' => $approval->institute_id,
                'template_key' => 'approval_escalated',
            ]);
            $this->audit->log('approval.escalated', $approval, after: ['escalated_to' => $escalateTo]);
        }

        return $overdue->count();
    }

    public function canDecide(User $user, Approval $approval): bool
    {
        return $user->hasRole($approval->approver_role)
            || $user->hasRole('Institute Admin')
            || $user->hasRole('Platform Admin');
    }

    private function handler(string $action): ?ApprovalHandler
    {
        // Action keys contain dots (e.g. fee.discount), so index the array
        // directly rather than via config() dot-notation.
        $class = config('approvals.handlers', [])[$action] ?? null;

        return $class ? app($class) : null;
    }
}
