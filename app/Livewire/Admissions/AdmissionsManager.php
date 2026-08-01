<?php

namespace App\Livewire\Admissions;

use App\Enums\EnrollmentStatus;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\Admissions\AdmissionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AdmissionsManager extends Component
{
    // New admission - student
    public string $s_name = '';
    public ?string $s_dob = null;
    public string $s_gender = '';
    public string $s_phone = '';
    public string $s_email = '';
    public ?int $s_branch_id = null;
    // Guardian
    public string $g_name = '';
    public string $g_relation = '';
    public string $g_phone = '';
    // Enrollment + consent
    public ?int $course_id = null;
    public bool $consent_data = true;
    public bool $consent_comm = true;

    // Panels
    public ?int $withdrawId = null;
    public string $withdrawReason = '';
    public ?int $profileId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('admission.view'), 403);
    }

    public function getIsMinorProperty(): bool
    {
        return $this->s_dob && Carbon::parse($this->s_dob)->age < 18;
    }

    public function admit(AdmissionService $service): void
    {
        abort_unless(Auth::user()?->can('admission.create'), 403);

        $this->validate([
            's_name' => ['required', 'string', 'max:255'],
            's_dob' => ['nullable', 'date', 'before:today'],
            's_gender' => ['nullable', 'string', 'max:20'],
            's_phone' => ['nullable', 'string', 'max:20'],
            's_email' => ['nullable', 'email'],
            's_branch_id' => ['required', 'exists:branches,id'],
            'g_name' => ['nullable', 'string', 'max:255'],
            'g_phone' => ['nullable', 'string', 'max:20'],
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        if ($this->isMinor && ! $this->g_name) {
            $this->addError('g_name', 'A minor needs a primary guardian.');

            return;
        }

        DB::transaction(function () use ($service) {
            $student = Student::create([
                'institute_id' => current_institute()?->id,
                'branch_id' => $this->s_branch_id,
                'name' => $this->s_name,
                'dob' => $this->s_dob,
                'gender' => $this->s_gender ?: null,
                'phone' => $this->s_phone ?: null,
                'email' => $this->s_email ?: null,
            ]);

            if ($this->g_name) {
                $guardian = Guardian::create([
                    'institute_id' => current_institute()?->id,
                    'name' => $this->g_name,
                    'relation' => $this->g_relation ?: null,
                    'phone' => $this->g_phone ?: null,
                ]);
                $student->guardians()->attach($guardian->id, ['is_primary' => true, 'relationship' => $this->g_relation ?: 'guardian']);
            }

            $service->enroll($student, $this->course_id, consentTypes: $this->consentTypes());
        });

        $this->reset(['s_name', 's_dob', 's_gender', 's_phone', 's_email', 'g_name', 'g_relation', 'g_phone', 'course_id']);
        $this->consent_data = true;
        $this->consent_comm = true;
        session()->flash('ok', 'Student admitted.');
    }

    public function activate(int $enrollmentId, AdmissionService $service): void
    {
        abort_unless(Auth::user()?->can('admission.update'), 403);

        try {
            $service->activate(Enrollment::findOrFail($enrollmentId), $this->consentTypes());
            session()->flash('ok', 'Admission completed.');
        } catch (\DomainException $e) {
            $this->addError('activate', $e->getMessage());
        }
    }

    public function openWithdraw(int $id): void
    {
        $this->withdrawId = $id;
        $this->withdrawReason = '';
    }

    public function withdraw(AdmissionService $service): void
    {
        abort_unless(Auth::user()?->can('admission.update'), 403);
        $service->withdraw(Enrollment::findOrFail($this->withdrawId), $this->withdrawReason ?: null);
        $this->reset(['withdrawId', 'withdrawReason']);
    }

    public function viewProfile(int $studentId): void
    {
        $this->profileId = $studentId;
    }

    private function consentTypes(): array
    {
        return array_values(array_filter([
            $this->consent_data ? 'data' : null,
            $this->consent_comm ? 'communication' : null,
        ]));
    }

    public function render()
    {
        return view('livewire.admissions.admissions-manager', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'courses' => Course::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'provisional' => Enrollment::with(['student', 'course'])
                ->where('status', EnrollmentStatus::Provisional->value)->latest()->get(),
            'enrollments' => Enrollment::with(['student', 'course'])
                ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::OnHold->value, EnrollmentStatus::Withdrawn->value])
                ->latest()->limit(100)->get(),
            'profile' => $this->profileId
                ? Student::with(['guardians', 'enrollments.course'])->find($this->profileId)
                : null,
        ]);
    }
}
