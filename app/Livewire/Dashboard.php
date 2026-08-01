<?php

namespace App\Livewire;

use App\Enums\EnquiryStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\Enquiry;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount()
    {
        if (Auth::user()?->isPortalUser()) {
            return redirect()->route('portal');
        }
    }

    public function render()
    {
        $u = Auth::user();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        // Attendance overall (present+late over marked, excluding excused).
        $attRows = AttendanceRecord::query()->selectRaw(
            "SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) present, ".
            "SUM(CASE WHEN status <> 'excused' THEN 1 ELSE 0 END) total"
        )->first();
        $attPct = ($attRows && $attRows->total > 0) ? (int) round($attRows->present / $attRows->total * 100) : 0;

        $enqTotal = Enquiry::count();
        $converted = Enquiry::where('status', EnquiryStatus::Converted->value)->count();

        return view('livewire.dashboard', [
            'user' => $u,
            'kpis' => [
                'students' => Enrollment::whereIn('status', EnrollmentStatus::liveValues())->distinct('student_id')->count('student_id'),
                'collected' => (int) Payment::whereDate('payment_date', '>=', $monthStart)->sum('amount'),
                'outstanding' => (int) Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance'),
                'attendance' => $attPct,
                'open_enquiries' => Enquiry::open()->count(),
                'conversion' => $enqTotal > 0 ? (int) round($converted / $enqTotal * 100) : 0,
                'due_today' => Enquiry::dueBy($today)->count(),
                'collected_today' => (int) Payment::whereDate('payment_date', $today)->sum('amount'),
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
