<?php

use App\Http\Controllers\IdCardController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PortalPaymentController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Admissions\AdmissionsManager;
use App\Livewire\Approvals\ApprovalInbox;
use App\Livewire\Assessments\AssessmentManager;
use App\Livewire\Attendance\AttendanceRegister;
use App\Livewire\Batches\BatchManager;
use App\Livewire\Branches\BranchManager;
use App\Livewire\Courses\CourseSubjectManager;
use App\Livewire\Dashboard;
use App\Livewire\Enquiries\EnquiryManager;
use App\Livewire\Exams\ExamManager;
use App\Livewire\Exceptions\OverrideLog;
use App\Livewire\Fees\BillingManager;
use App\Livewire\Fees\FeeSetupManager;
use App\Livewire\Hr\PayrollManager;
use App\Livewire\Hr\StaffAttendanceRegister;
use App\Livewire\IdCards\IdCardManager;
use App\Livewire\Import\ImportManager;
use App\Livewire\Materials\MaterialManager;
use App\Livewire\Notifications\FailedMessages;
use App\Livewire\Portal\PortalAttendance;
use App\Livewire\Portal\PortalExamAttempt;
use App\Livewire\Portal\PortalExams;
use App\Livewire\Portal\PortalFees;
use App\Livewire\Portal\PortalHome;
use App\Livewire\Portal\PortalMaterials;
use App\Livewire\Portal\PortalResults;
use App\Livewire\Portal\PortalTimetable;
use App\Livewire\Reports\Reports;
use App\Livewire\Security\TwoFactorSettings;
use App\Livewire\Sessions\SessionManager;
use App\Livewire\Settings\SettingsManager;
use App\Livewire\Staff\StaffManager;
use App\Livewire\Timetable\TimetableManager;
use App\Livewire\Website\WebsiteManager;
use Illuminate\Support\Facades\Route;

// Gateway webhook (signature-verified, no auth/CSRF).
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])->middleware('throttle:60,1')->name('webhooks.payment');

// Public landing: a sign-in gateway. Authenticated users go to their home.
Route::get('/', function () {
    if (! auth()->check()) {
        return view('home');
    }

    return redirect()->route(auth()->user()->isPortalUser() ? 'portal' : 'dashboard');
})->name('home');

// Lightweight health endpoint for monitoring (Phase 17 extends this).
Route::get('/up', fn () => response()->json(['status' => 'ok', 'phase' => 7]))->name('health');

// Public marketing website — data-driven from published branches/courses.
Route::prefix('site')->group(function () {
    Route::get('/', [PublicSiteController::class, 'home'])->name('site.home');
    Route::get('/branches/{slug}', [PublicSiteController::class, 'branch'])->name('site.branch');
    Route::get('/courses/{slug}', [PublicSiteController::class, 'course'])->name('site.course');
    Route::post('/enquiry', [PublicSiteController::class, 'storeEnquiry'])->middleware('throttle:10,1')->name('site.enquiry');
});

// Student and parent portal (read-only, ownership-scoped, mobile-first PWA).
Route::middleware('auth')->prefix('portal')->group(function () {
    Route::get('/', PortalHome::class)->name('portal');
    Route::get('/fees', PortalFees::class)->name('portal.fees');
    Route::get('/attendance', PortalAttendance::class)->name('portal.attendance');
    Route::get('/results', PortalResults::class)->name('portal.results');
    Route::get('/timetable', PortalTimetable::class)->name('portal.timetable');
    Route::get('/exams', PortalExams::class)->name('portal.exams');
    Route::get('/exams/{exam}', PortalExamAttempt::class)->name('portal.exam');
    Route::get('/materials', PortalMaterials::class)->name('portal.materials');
    Route::get('/pay/{invoice}', [PortalPaymentController::class, 'pay'])->name('portal.pay');
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
    Route::get('/exams', ExamManager::class)->name('exams');
    Route::get('/materials', MaterialManager::class)->name('materials');
    Route::get('/report-cards/{assessment}/{student}', [ReportCardController::class, 'show'])->name('report-cards.show');

    Route::get('/id-cards', IdCardManager::class)->name('id-cards');
    Route::get('/id-cards/sheet', [IdCardController::class, 'sheet'])->name('id-cards.sheet');

    Route::get('/fees', BillingManager::class)->name('fees');
    Route::get('/fees/setup', FeeSetupManager::class)->name('fees.setup');
    Route::get('/overrides', OverrideLog::class)->name('overrides');
    Route::get('/reports', Reports::class)->name('reports');
    Route::get('/reports/export/{report}', [ReportExportController::class, 'export'])->name('reports.export');
    Route::get('/messages', FailedMessages::class)->name('messages');
    Route::get('/import', ImportManager::class)->name('import');
    Route::get('/import/template/students', [ImportTemplateController::class, 'students'])->name('import.template.students');
    Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');

    Route::get('/settings', SettingsManager::class)->name('settings');
    Route::get('/branches', BranchManager::class)->name('branches');
    Route::get('/courses', CourseSubjectManager::class)->name('courses');
    Route::get('/sessions', SessionManager::class)->name('sessions');
    Route::get('/staff', StaffManager::class)->name('staff');
    Route::get('/staff-attendance', StaffAttendanceRegister::class)->name('staff-attendance');
    Route::get('/payroll', PayrollManager::class)->name('payroll');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::get('/website', WebsiteManager::class)->name('website');

    Route::get('/security', TwoFactorSettings::class)->name('security');

    Route::view('/ui', 'ui.gallery')->name('ui.gallery');
});

require __DIR__.'/auth.php';
