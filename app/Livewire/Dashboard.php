<?php

namespace App\Livewire;

use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\Enquiry;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Reports\ReportService;
use App\Services\Setup\SetupChecklist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    /** today | 7d | 30d | month | quarter | year | custom */
    #[Url]
    public string $range = '30d';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    /** Accent palette for series (brand action first). */
    private array $palette = ['#4338ca', '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899'];

    public function mount()
    {
        if (Auth::user()?->isPortalUser()) {
            return redirect()->route('portal');
        }
    }

    public function setRange(string $range): void
    {
        $this->range = $range;
        if ($range !== 'custom') {
            $this->from = $this->to = null;
        }
    }

    public function updatedFrom(): void
    {
        $this->range = 'custom';
    }

    public function updatedTo(): void
    {
        $this->range = 'custom';
    }

    /** @return array{0:string,1:string} [from, to] as Y-m-d */
    private function bounds(): array
    {
        $t = now();

        return match ($this->range) {
            'today' => [$t->copy()->toDateString(), $t->toDateString()],
            '7d' => [$t->copy()->subDays(6)->toDateString(), $t->toDateString()],
            'month' => [$t->copy()->startOfMonth()->toDateString(), $t->toDateString()],
            'quarter' => [$t->copy()->subDays(89)->toDateString(), $t->toDateString()],
            'year' => [$t->copy()->startOfYear()->toDateString(), $t->toDateString()],
            'custom' => [$this->from ?: $t->copy()->subDays(29)->toDateString(), $this->to ?: $t->toDateString()],
            default => [$t->copy()->subDays(29)->toDateString(), $t->toDateString()],
        };
    }

    /**
     * Build labels + aligned data for every day in [from,to]. Weekly buckets
     * when the span is long, so the x-axis stays readable.
     *
     * @param  array<string,int|float>  ...$series
     * @return array{labels:array<int,string>,rows:array<int,array<int,float>>}
     */
    private function timeAxis(string $from, string $to, array ...$series): array
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $spanDays = $start->diffInDays($end) + 1;
        $step = $spanDays > 180 ? 'month' : ($spanDays > 45 ? 'week' : 'day');

        $labels = [];
        $rows = array_fill(0, count($series), []);
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $bucketEnd = match ($step) {
                'month' => $cursor->copy()->endOfMonth()->min($end),
                'week' => $cursor->copy()->addDays(6)->min($end),
                default => $cursor->copy(),
            };
            $labels[] = $step === 'day' ? $cursor->format('d M') : $cursor->format('d M').'–'.$bucketEnd->format('d M');

            foreach ($series as $i => $daily) {
                $sum = 0;
                foreach ($daily as $date => $val) {
                    if ($date >= $cursor->toDateString() && $date <= $bucketEnd->toDateString()) {
                        $sum += $val;
                    }
                }
                $rows[$i][] = $sum;
            }
            $cursor = $bucketEnd->copy()->addDay();
        }

        return ['labels' => $labels, 'rows' => $rows];
    }

    private function baseOptions(bool $legend = false): array
    {
        return [
            'responsive' => true,
            'plugins' => ['legend' => ['display' => $legend, 'position' => 'bottom']],
        ];
    }

    public function render(ReportService $reports, SetupChecklist $setup)
    {
        $u = Auth::user();
        [$from, $to] = $this->bounds();
        $today = now()->toDateString();

        $setupProgress = $setup->shouldNudge($u) ? $setup->progress($u) : null;

        // Previous period of equal length, for deltas.
        $span = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $prevTo = Carbon::parse($from)->subDay()->toDateString();
        $prevFrom = Carbon::parse($prevTo)->subDays($span - 1)->toDateString();

        $collected = (int) Payment::where('status', 'completed')->whereBetween('payment_date', [$from, $to])->sum('amount');
        $collectedPrev = (int) Payment::where('status', 'completed')->whereBetween('payment_date', [$prevFrom, $prevTo])->sum('amount');
        $newEnq = Enquiry::whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
        $newEnqPrev = Enquiry::whereBetween('created_at', [$prevFrom.' 00:00:00', $prevTo.' 23:59:59'])->count();
        $newAdm = Enrollment::whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
        $newAdmPrev = Enrollment::whereBetween('created_at', [$prevFrom.' 00:00:00', $prevTo.' 23:59:59'])->count();

        $delta = fn ($now, $prev) => $prev > 0 ? (int) round(($now - $prev) / $prev * 100) : ($now > 0 ? 100 : 0);

        // Attendance % overall.
        $att = AttendanceRecord::selectRaw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) p, SUM(CASE WHEN status <> 'excused' THEN 1 ELSE 0 END) t")->first();
        $attPct = ($att && $att->t > 0) ? (int) round($att->p / $att->t * 100) : 0;

        // ---- Chart configs -------------------------------------------------
        $money = fn (array $arr) => array_map(fn ($v) => round($v / 100, 2), $arr);

        // 1) Collections over time (area line)
        $axis = $this->timeAxis($from, $to, $reports->collectionsByDayRange($from, $to));
        $collectionsChart = [
            'type' => 'line', '_currency' => true,
            'data' => ['labels' => $axis['labels'], 'datasets' => [[
                'label' => 'Collected', 'data' => $money($axis['rows'][0]),
                'borderColor' => $this->palette[0], 'backgroundColor' => $this->palette[0].'22',
                'fill' => true, 'tension' => 0.35, 'pointRadius' => 2,
            ]]],
            'options' => $this->baseOptions() + ['scales' => ['y' => ['beginAtZero' => true]]],
        ];

        // 2) Enquiries vs admissions over time (multi-line)
        $ax2 = $this->timeAxis($from, $to, $reports->enquiriesByDayRange($from, $to), $reports->enrollmentsByDayRange($from, $to));
        $funnelTrend = [
            'type' => 'line',
            'data' => ['labels' => $ax2['labels'], 'datasets' => [
                ['label' => 'New enquiries', 'data' => $ax2['rows'][0], 'borderColor' => $this->palette[1], 'backgroundColor' => $this->palette[1].'22', 'tension' => 0.35, 'pointRadius' => 2, 'fill' => true],
                ['label' => 'New admissions', 'data' => $ax2['rows'][1], 'borderColor' => $this->palette[2], 'backgroundColor' => $this->palette[2].'22', 'tension' => 0.35, 'pointRadius' => 2, 'fill' => true],
            ]],
            'options' => $this->baseOptions(legend: true) + ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
        ];

        // 3) Collections by mode (doughnut)
        $byMode = $reports->collectionsByMode($from, $to);
        $modeChart = [
            'type' => 'doughnut', '_currency' => true,
            'data' => ['labels' => array_map('ucfirst', array_keys($byMode)), 'datasets' => [[
                'data' => $money(array_values($byMode)), 'backgroundColor' => array_slice($this->palette, 0, max(1, count($byMode))), 'borderWidth' => 0,
            ]]],
            'options' => $this->baseOptions(legend: true) + ['cutout' => '62%'],
        ];

        // 4) Enquiry funnel (bar)
        $funnel = $reports->enquiryFunnel();
        $funnelChart = [
            'type' => 'bar',
            'data' => ['labels' => array_keys($funnel), 'datasets' => [[
                'label' => 'Enquiries', 'data' => array_values($funnel), 'backgroundColor' => $this->palette[3], 'borderRadius' => 6,
            ]]],
            'options' => $this->baseOptions() + ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
        ];

        // 5) Outstanding ageing (bar, currency)
        $ageing = $reports->outstandingAgeing();
        $ageingChart = [
            'type' => 'bar', '_currency' => true,
            'data' => ['labels' => array_keys($ageing), 'datasets' => [[
                'label' => 'Outstanding', 'data' => $money(array_values($ageing)),
                'backgroundColor' => [$this->palette[2], $this->palette[3], $this->palette[4], '#991b1b'], 'borderRadius' => 6,
            ]]],
            'options' => $this->baseOptions() + ['scales' => ['y' => ['beginAtZero' => true]]],
        ];

        // 6) Attendance by batch (horizontal bar, percent)
        $attByBatch = $reports->attendanceByBatch();
        $attChart = [
            'type' => 'bar', '_percent' => true,
            'data' => ['labels' => array_keys($attByBatch), 'datasets' => [[
                'label' => 'Attendance', 'data' => array_map(fn ($bp) => round($bp / 100, 1), array_values($attByBatch)),
                'backgroundColor' => $this->palette[5], 'borderRadius' => 6,
            ]]],
            'options' => $this->baseOptions() + ['indexAxis' => 'y', 'scales' => ['x' => ['beginAtZero' => true, 'max' => 100]]],
        ];

        return view('livewire.dashboard', [
            'user' => $u,
            'setupProgress' => $setupProgress,
            'range' => $this->range,
            'rangeLabel' => Carbon::parse($from)->format('d M Y').' – '.Carbon::parse($to)->format('d M Y'),
            'kpis' => [
                'collected' => $collected, 'collected_delta' => $delta($collected, $collectedPrev),
                'new_enquiries' => $newEnq, 'new_enquiries_delta' => $delta($newEnq, $newEnqPrev),
                'new_admissions' => $newAdm, 'new_admissions_delta' => $delta($newAdm, $newAdmPrev),
                'outstanding' => (int) Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance'),
                'active_students' => Enrollment::whereIn('status', EnrollmentStatus::liveValues())->distinct('student_id')->count('student_id'),
                'attendance' => $attPct,
                'open_enquiries' => Enquiry::open()->count(),
            ],
            'charts' => [
                'collections' => $collectionsChart,
                'funnelTrend' => $funnelTrend,
                'mode' => $modeChart,
                'funnel' => $funnelChart,
                'ageing' => $ageingChart,
                'attendance' => $attChart,
            ],
            'recentAdmissions' => $u->can('admission.view')
                ? Enrollment::with(['student', 'course'])->latest()->limit(6)->get() : collect(),
            'recentPayments' => $u->can('fee.view')
                ? Payment::with('student')->latest()->limit(6)->get() : collect(),
            'dueFollowUps' => $u->can('enquiry.view')
                ? Enquiry::dueBy($today)->with('course')->orderBy('next_follow_up_on')->limit(6)->get() : collect(),
        ]);
    }
}
