<?php

namespace App\Services\Fees;

use App\Models\Approval;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Approvals\ApprovalHandler;

/**
 * Applies an approved fee discount. On rejection nothing is reverted, because
 * the discount is only ever applied on approval.
 */
class DiscountApprovalHandler implements ApprovalHandler
{
    public function __construct(private DiscountService $discounts) {}

    public function handleApproved(Approval $approval): void
    {
        // The approval already validated authority; apply the effect regardless
        // of the decider's branch scope.
        $invoice = Invoice::withoutGlobalScopes()->find($approval->meta['invoice_id'] ?? $approval->approvable_id);
        if (! $invoice) {
            return;
        }

        $staffId = User::find($approval->decided_by)?->staff?->id;

        $this->discounts->applyToInvoice(
            $invoice,
            $approval->meta['kind'] ?? 'fixed',
            (int) ($approval->meta['value'] ?? $approval->amount),
            $approval->meta['reason'] ?? 'Approved discount',
            $staffId,
        );
    }

    public function handleRejected(Approval $approval): void
    {
        // No state change to revert.
    }
}
