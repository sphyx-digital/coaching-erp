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
    // New admission - student (structured name + ISD phone)
    public string $s_title = '';

    public string $s_first = '';

    public string $s_middle = '';

    public string $s_last = '';

    public ?string $s_dob = null;

    public string $s_gender = '';

    public string $s_dial = '+91';

    public string $s_phone = '';

    public string $s_email = '';

    public ?int $s_branch_id = null;

    // Guardian
    public string $g_title = '';

    public string $g_first = '';

    public string $g_middle = '';

    public string $g_last = '';

    public string $g_relation = '';

    public string $g_dial = '+91';

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

    private function composeName(?string $first, ?string $middle, ?string $last): string
    {
        return trim(collect([$first, $middle, $last])->filter()->implode(' '));
    }

    public function admit(AdmissionService $service): void
    {
        abort_unless(Auth::user()?->can('admission.create'), 403);

        $this->validate([
            's_first' => ['required', 'string', 'max:255'],
            's_last' => ['nullable', 'string', 'max:255'],
            's_dob' => ['nullable', 'date', 'before:today'],
            's_gender' => ['nullable', 'string', 'max:20'],
            's_phone' => ['nullable', 'string', 'max:20'],
            's_email' => ['nullable', 'email'],
            's_branch_id' => ['required', 'exists:branches,id'],
            'g_phone' => ['nullable', 'string', 'max:20'],
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        if ($this->isMinor && ! $this->g_first) {
            $this->addError('g_first', 'A minor needs a primary guardian.');

            return;
        }

        DB::transaction(function () use ($service) {
            $student = Student::create([
                'institute_id' => current_institute()?->id,
                'branch_id' => $this->s_branch_id,
                'name' => $this->composeName($this->s_first, $this->s_middle, $this->s_last),
                'title' => $this->s_title ?: null,
                'first_name' => $this->s_first,
                'middle_name' => $this->s_middle ?: null,
                'last_name' => $this->s_last ?: null,
                'dob' => $this->s_dob,
                'gender' => $this->s_gender ?: null,
                'dial_code' => $this->s_dial,
                'phone' => $this->s_phone ?: null,
                'email' => $this->s_email ?: null,
            ]);

            if ($this->g_first) {
                $guardian = Guardian::create([
                    'institute_id' => current_institute()?->id,
                    'name' => $this->composeName($this->g_first, $this->g_middle, $this->g_last),
                    'title' => $this->g_title ?: null,
                    'first_name' => $this->g_first,
                    'middle_name' => $this->g_middle ?: null,
                    'last_name' => $this->g_last ?: null,
                    'relation' => $this->g_relation ?: null,
                    'dial_code' => $this->g_dial,
                    'phone' => $this->g_phone ?: null,
                ]);
                $student->guardians()->attach($guardian->id, ['is_primary' => true, 'relationship' => $this->g_relation ?: 'guardian']);
            }

            $service->enroll($student, $this->course_id, consentTypes: $this->consentTypes());
        });

        $this->reset(['s_title', 's_first', 's_middle', 's_last', 's_dob', 's_gender', 's_phone', 's_email', 'g_title', 'g_first', 'g_middle', 'g_last', 'g_relation', 'g_phone', 'course_id']);
        $this->s_dial = '+91';
        $this->g_dial = '+91';
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
