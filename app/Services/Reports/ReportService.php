<?php

namespace App\Services\Reports;

use App\Enums\EnquiryStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\Enquiry;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReportCard;

/**
 * Read-only aggregation for dashboards and reports. Queries respect the same
 * branch scoping as the modules (via the models' global scopes), so a Branch
 * Admin's numbers exclude other branches automatically.
 */
class ReportService
{
    /** Collections grouped by payment mode (completed only). @return array<string,int> */
    public function collectionsByMode(?string $from = null, ?string $to = null): array
    {
        return Payment::where('status', 'completed')
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->selectRaw('mode, SUM(amount) total')->groupBy('mode')
            ->pluck('total', 'mode')->map(fn ($v) => (int) $v)->all();
    }

    /** Daily collection for the last N days. @return array<string,int> date=>paise */
    public function collectionsByDay(int $days = 14): array
    {
        $from = now()->subDays($days - 1)->toDateString();

        return Payment::where('status', 'completed')->whereDate('payment_date', '>=', $from)
            ->selectRaw('payment_date, SUM(amount) total')->groupBy('payment_date')
            ->orderBy('payment_date')->pluck('total', 'payment_date')->map(fn ($v) => (int) $v)->all();
    }

    /** Outstanding by ageing bucket. @return array<string,int> */
    public function outstandingAgeing(): array
    {
        $buckets = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $today = now();

        Invoice::whereNotIn('status', ['paid', 'cancelled'])->where('balance', '>', 0)
            ->get(['invoice_date', 'balance'])->each(function ($inv) use (&$buckets, $today) {
                $age = $inv->invoice_date ? $inv->invoice_date->diffInDays($today) : 0;
                $key = $age <= 30 ? '0-30' : ($age <= 60 ? '31-60' : ($age <= 90 ? '61-90' : '90+'));
                $buckets[$key] += (int) $inv->balance;
            });

        return $buckets;
    }

    /** Enquiry funnel counts. @return array<string,int> */
    public function enquiryFunnel(): array
    {
        $out = [];
        foreach (EnquiryStatus::cases() as $s) {
            $out[$s->label()] = Enquiry::where('status', $s->value)->count();
        }

        return $out;
    }

    /** Attendance percentage per batch. @return array<string,int> batchName=>percentBp */
    public function attendanceByBatch(): array
    {
        $out = [];
        foreach (Batch::where('is_active', true)->get() as $batch) {
            $row = AttendanceRecord::query()
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                ->where('attendance_sessions.batch_id', $batch->id)
                ->selectRaw("SUM(CASE WHEN attendance_records.status IN ('present','late') THEN 1 ELSE 0 END) p, SUM(CASE WHEN attendance_records.status <> 'excused' THEN 1 ELSE 0 END) t")
                ->first();
            if ($row && $row->t > 0) {
                $out[$batch->name] = (int) round($row->p / $row->t * 10000);
            }
        }

        return $out;
    }

    /** Headline KPIs. */
    public function kpis(): array
    {
        $sessionId = active_session()?->id;
        $monthStart = now()->startOfMonth()->toDateString();
        $passBp = (int) client_setting('pass_percent_bp', 4000);

        $enqTotal = Enquiry::count();
        $converted = Enquiry::where('status', EnquiryStatus::Converted->value)->count();
        $cards = ReportCard::where('status', 'published')->get(['percentage_bp']);
        $passed = $cards->where('percentage_bp', '>=', $passBp)->count();

        return [
            'conversion_bp' => $enqTotal ? (int) round($converted / $enqTotal * 10000) : 0,
            'admissions_session' => Enrollment::when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->whereIn('status', EnrollmentStatus::liveValues())->count(),
            'billed_month' => (int) Invoice::whereDate('invoice_date', '>=', $monthStart)->sum('total'),
            'collected_month' => (int) Payment::where('status', 'completed')->whereDate('payment_date', '>=', $monthStart)->sum('amount'),
            'outstanding' => (int) Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance'),
            'pass_rate_bp' => $cards->count() ? (int) round($passed / $cards->count() * 10000) : 0,
        ];
    }
}
