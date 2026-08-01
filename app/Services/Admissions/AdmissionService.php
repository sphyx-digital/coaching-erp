<?php

namespace App\Services\Admissions;

use App\Enums\EnrollmentStatus;
use App\Models\ConsentRecord;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Numbering\NumberingService;
use DomainException;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    public function __construct(
        private NumberingService $numbering,
        private AuditLogger $audit,
    ) {}

    /**
     * Enroll a student into a course for a session, assigning an institute-wide
     * admission number on first enrollment and recording consent.
     *
     * A minor must have a primary guardian. A duplicate live enrollment for the
     * same student, course and session is blocked.
     *
     * @param  array<string>  $consentTypes  granted consent types (data, communication)
     */
    public function enroll(
        Student $student,
        int $courseId,
        ?int $sessionId = null,
        ?int $branchId = null,
        array $consentTypes = [],
        EnrollmentStatus $status = EnrollmentStatus::Active,
    ): Enrollment {
        $sessionId ??= active_session()?->id;
        $branchId ??= $student->branch_id;

        if ($student->isMinor() && ! $this->hasPrimaryGuardian($student)) {
            throw new DomainException('A minor must have a primary guardian before enrolling.');
        }

        if ($this->hasLiveEnrollment($student->id, $courseId, $sessionId)) {
            throw new DomainException('This student already has a live enrollment in this course for the session.');
        }

        return DB::transaction(function () use ($student, $courseId, $sessionId, $branchId, $consentTypes, $status) {
            if (! $student->admission_number) {
                $student->admission_number = $this->numbering->next($student->institute_id, 'admission');
                $student->save();
            }

            $enrollment = Enrollment::create([
                'institute_id' => $student->institute_id,
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'course_id' => $courseId,
                'academic_session_id' => $sessionId,
                'status' => $status->value,
                'enrolled_on' => now()->toDateString(),
            ]);

            $this->recordConsent($student, $enrollment, $consentTypes);

            $this->audit->log('enrollment.created', $enrollment, after: [
                'admission_number' => $student->admission_number,
                'course_id' => $courseId,
                'status' => $status->value,
            ]);

            return $enrollment;
        });
    }

    /**
     * Activate a provisional enrollment (for example one created by an enquiry
     * conversion in Phase 4), applying the same guards and consent.
     */
    public function activate(Enrollment $enrollment, array $consentTypes = []): Enrollment
    {
        $student = $enrollment->student;

        if ($student->isMinor() && ! $this->hasPrimaryGuardian($student)) {
            throw new DomainException('A minor must have a primary guardian before enrolling.');
        }

        return DB::transaction(function () use ($enrollment, $student, $consentTypes) {
            if (! $student->admission_number) {
                $student->admission_number = $this->numbering->next($student->institute_id, 'admission');
                $student->save();
            }

            $enrollment->update([
                'status' => EnrollmentStatus::Active->value,
                'enrolled_on' => $enrollment->enrolled_on ?? now()->toDateString(),
            ]);

            $this->recordConsent($student, $enrollment, $consentTypes);
            $this->audit->log('enrollment.activated', $enrollment, after: ['status' => 'active']);

            return $enrollment;
        });
    }

    /**
     * Withdraw an enrollment, retaining history (never deletes the student).
     */
    public function withdraw(Enrollment $enrollment, ?string $reason = null): Enrollment
    {
        $from = $enrollment->status;

        $enrollment->update([
            'status' => EnrollmentStatus::Withdrawn->value,
            'withdrawn_on' => now()->toDateString(),
            'withdraw_reason' => $reason,
        ]);

        $this->audit->log('enrollment.withdrawn', $enrollment,
            before: ['status' => $from],
            after: ['status' => EnrollmentStatus::Withdrawn->value, 'reason' => $reason],
        );

        return $enrollment;
    }

    public function hasPrimaryGuardian(Student $student): bool
    {
        return $student->guardians()->wherePivot('is_primary', true)->exists();
    }

    public function hasLiveEnrollment(int $studentId, int $courseId, ?int $sessionId): bool
    {
        return Enrollment::query()
            ->withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('academic_session_id', $sessionId)
            ->whereIn('status', EnrollmentStatus::liveValues())
            ->exists();
    }

    /**
     * @param  array<string>  $types
     */
    private function recordConsent(Student $student, Enrollment $enrollment, array $types): void
    {
        $guardianId = $student->guardians()->wherePivot('is_primary', true)->value('guardians.id');

        foreach ($types as $type) {
            ConsentRecord::updateOrCreate(
                ['student_id' => $student->id, 'enrollment_id' => $enrollment->id, 'consent_type' => $type],
                [
                    'institute_id' => $student->institute_id,
                    'guardian_id' => $guardianId,
                    'granted' => true,
                    'decided_at' => now(),
                ],
            );
        }
    }
}
