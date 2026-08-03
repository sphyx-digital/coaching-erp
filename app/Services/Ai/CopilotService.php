<?php

namespace App\Services\Ai;

use App\Enums\EnrollmentStatus;
use App\Models\Enquiry;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Reports\ReportService;

/**
 * The institute AI copilot. Builds a bounded, branch-scoped data snapshot and
 * asks Anthropic to answer questions grounded in it — no free-form DB access,
 * so answers stay within what the current user is allowed to see.
 */
class CopilotService
{
    public function __construct(
        private AnthropicClient $client,
        private ReportService $reports,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $history
     */
    public function answer(User $user, string $question, array $history = []): string
    {
        $snapshot = $this->snapshot();
        $system = $this->systemPrompt($snapshot);

        $messages = array_merge($history, [['role' => 'user', 'content' => $question]]);

        return $this->client->message($system, $messages);
    }

    private function systemPrompt(array $snapshot): string
    {
        $institute = current_institute()?->name ?? 'the institute';
        $today = now()->format('l, d M Y');
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
        You are the AI assistant for {$institute}, a coaching-institute ERP. Today is {$today}.
        Answer the user's questions using ONLY the DATA SNAPSHOT below — do not invent numbers or names.
        All money values are already in Indian Rupees. Be concise and practical: use short bullet points
        or small tables, lead with the answer, and add a one-line insight when useful. If the snapshot
        does not contain the information, say so plainly and suggest where in the ERP to look.

        DATA SNAPSHOT (JSON):
        {$json}
        PROMPT;
    }

    /** Bounded, read-only snapshot of the institute's current state. */
    private function snapshot(): array
    {
        $rupees = fn ($paise) => round(((int) $paise) / 100, 2);
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $overdue = Invoice::with('student')
            ->whereNotIn('status', ['paid', 'cancelled'])->where('balance', '>', 0)
            ->orderByDesc('balance')->limit(15)->get()
            ->map(fn ($i) => [
                'invoice' => $i->invoice_number,
                'student' => $i->student?->name,
                'balance' => $rupees($i->balance),
                'days_since_invoice' => $i->invoice_date ? (int) $i->invoice_date->diffInDays(now()) : null,
            ])->all();

        $dueFollowUps = Enquiry::dueBy($today)->with('course')->orderBy('next_follow_up_on')->limit(15)->get()
            ->map(fn ($e) => [
                'enquiry' => $e->enquiry_number,
                'name' => $e->name,
                'phone' => $e->phone,
                'course' => $e->course?->name,
                'due' => optional($e->next_follow_up_on)->toDateString(),
            ])->all();

        $recentPayments = Payment::with('student')->latest()->limit(10)->get()
            ->map(fn ($p) => [
                'receipt' => $p->receipt_number,
                'student' => $p->student?->name,
                'amount' => $rupees($p->amount),
                'mode' => $p->mode,
                'date' => optional($p->payment_date)->toDateString(),
            ])->all();

        return [
            'headline' => [
                'active_students' => Enrollment::whereIn('status', EnrollmentStatus::liveValues())->distinct('student_id')->count('student_id'),
                'collected_this_month' => $rupees(Payment::where('status', 'completed')->whereDate('payment_date', '>=', $monthStart)->sum('amount')),
                'total_outstanding' => $rupees(Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance')),
                'open_enquiries' => Enquiry::open()->count(),
                'follow_ups_due_today' => Enquiry::dueBy($today)->count(),
            ],
            'collections_by_mode_this_month' => collect($this->reports->collectionsByMode($monthStart, $today))->map($rupees)->all(),
            'outstanding_ageing' => collect($this->reports->outstandingAgeing())->map($rupees)->all(),
            'enquiry_funnel' => $this->reports->enquiryFunnel(),
            'attendance_by_batch_percent' => collect($this->reports->attendanceByBatch())->map(fn ($bp) => round($bp / 100, 1))->all(),
            'top_outstanding_invoices' => $overdue,
            'follow_ups_due_today_list' => $dueFollowUps,
            'recent_payments' => $recentPayments,
        ];
    }
}
