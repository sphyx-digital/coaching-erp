<?php

use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportCardController;
use App\Livewire\Admissions\AdmissionsManager;
use App\Livewire\Approvals\ApprovalInbox;
use App\Livewire\Assessments\AssessmentManager;
use App\Livewire\Attendance\AttendanceRegister;
use App\Livewire\Batches\BatchManager;
use App\Livewire\Branches\BranchManager;
use App\Livewire\Courses\CourseSubjectManager;
use App\Livewire\Dashboard;
use App\Livewire\Enquiries\EnquiryManager;
use App\Livewire\Fees\BillingManager;
use App\Livewire\Fees\FeeSetupManager;
use App\Livewire\Portal\PortalAttendance;
use App\Livewire\Portal\PortalFees;
use App\Livewire\Portal\PortalHome;
use App\Livewire\Portal\PortalResults;
use App\Livewire\Portal\PortalTimetable;
use App\Livewire\Sessions\SessionManager;
use App\Livewire\Settings\SettingsManager;
use App\Livewire\Staff\StaffManager;
use App\Livewire\Timetable\TimetableManager;
use Illuminate\Support\Facades\Route;

// Public landing: a sign-in gateway. Authenticated users go to their home.
Route::get('/', function () {
    if (! auth()->check()) {
        return view('home');
    }

    return redirect()->route(auth()->user()->isPortalUser() ? 'portal' : 'dashboard');
})->name('home');

// Lightweight health endpoint for monitoring (Phase 17 extends this).
Route::get('/up', fn () => response()->json(['status' => 'ok', 'phase' => 7]))->name('health');

// Student and parent portal (read-only, ownership-scoped, mobile-first PWA).
Route::middleware('auth')->prefix('portal')->group(function () {
    Route::get('/', PortalHome::class)->name('portal');
    Route::get('/fees', PortalFees::class)->name('portal.fees');
    Route::get('/attendance', PortalAttendance::class)->name('portal.attendance');
    Route::get('/results', PortalResults::class)->name('portal.results');
    Route::get('/timetable', PortalTimetable::class)->name('portal.timetable');
});

// Authenticated back office (modules light up phase by phase).
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/approvals', ApprovalInbox::class)->name('approvals');

    Route::get('/enquiries', EnquiryManager::class)->name('enquiries');
    Route::get('/admissions', AdmissionsManager::class)->name('admissions');
    Route::get('/batches', BatchManager::class)->name('batches');
    Route::get('/timetable', TimetableManager::class)->name('timetable');
    Route::get('/attendance', AttendanceRegister::class)->name('attendance');
    Route::get('/assessments', AssessmentManager::class)->name('assessments');
    Route::get('/report-cards/{assessment}/{student}', [ReportCardController::class, 'show'])->name('report-cards.show');

    Route::get('/fees', BillingManager::class)->name('fees');
    Route::get('/fees/setup', FeeSetupManager::class)->name('fees.setup');
    Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');

    Route::get('/settings', SettingsManager::class)->name('settings');
    Route::get('/branches', BranchManager::class)->name('branches');
    Route::get('/courses', CourseSubjectManager::class)->name('courses');
    Route::get('/sessions', SessionManager::class)->name('sessions');
    Route::get('/staff', StaffManager::class)->name('staff');

    Route::view('/ui', 'ui.gallery')->name('ui.gallery');
});

require __DIR__.'/auth.php';
