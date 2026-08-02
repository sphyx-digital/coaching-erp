<?php

namespace App\Livewire\Reports;

use App\Models\Enquiry;
use App\Models\Invoice;
use App\Services\Reports\ReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Reports extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('report.view'), 403);
    }

    public function render(ReportService $reports)
    {
        $lowThreshold = (int) client_setting('low_attendance_threshold_bp', 7500);
        $byBatch = $reports->attendanceByBatch();

        return view('livewire.reports.reports', [
            'kpis' => $reports->kpis(),
            'byMode' => $reports->collectionsByMode(),
            'byDay' => $reports->collectionsByDay(),
            'ageing' => $reports->outstandingAgeing(),
            'funnel' => $reports->enquiryFunnel(),
            'attendance' => $byBatch,
            'alerts' => [
                'overdue_fees' => Invoice::whereNotIn('status', ['paid', 'cancelled'])->where('balance', '>', 0)->count(),
                'due_followups' => Enquiry::dueBy(now()->toDateString())->count(),
                'low_batches' => collect($byBatch)->filter(fn ($bp) => $bp < $lowThreshold)->count(),
            ],
        ]);
    }
}
