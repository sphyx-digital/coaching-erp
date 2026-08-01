<?php

namespace App\Services\Enquiries;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\EnquiryActivity;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use App\Services\Numbering\NumberingService;
use DomainException;
use Illuminate\Support\Facades\DB;

class EnquiryService
{
    public function __construct(
        private NumberingService $numbering,
        private AuditLogger $audit,
    ) {}

    /**
     * Capture a lead with an auto-assigned enquiry number.
     *
     * @param  array<string,mixed>  $data
     */
    public function create(array $data): Enquiry
    {
        return DB::transaction(function () use ($data) {
            $enquiry = new Enquiry($data);
            $enquiry->status = EnquiryStatus::New;
            $enquiry->enquiry_number = $this->numbering->next(
                (int) $data['institute_id'],
                'enquiry',
                $data['branch_id'] ?? null,
                $data['academic_session_id'] ?? null,
            );
            $enquiry->save();

            $this->audit->log('enquiry.created', $enquiry, after: [
                'enquiry_number' => $enquiry->enquiry_number,
                'name' => $enquiry->name,
            ]);

            return $enquiry;
        });
    }

    /**
     * A duplicate is the same contact and interested course within a session.
     * Flagged, never blocked.
     */
    public function isDuplicate(int $instituteId, ?string $phone, ?int $courseId, ?int $sessionId, ?int $ignoreId = null): bool
    {
        if (! $phone) {
            return false;
        }

        return Enquiry::query()
            ->where('institute_id', $instituteId)
            ->where('phone', $phone)
            ->where('course_id', $courseId)
            ->where('academic_session_id', $sessionId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Move an enquiry through the pipeline (not to Converted; use convert()).
     */
    public function transition(Enquiry $enquiry, EnquiryStatus $to, ?string $note = null): Enquiry
    {
        if ($to === EnquiryStatus::Converted) {
            throw new DomainException('Use convert() to convert an enquiry.');
        }

        $from = $enquiry->status;

        if (! $from->canTransitionTo($to)) {
            throw new DomainException("Cannot move an enquiry from {$from->label()} to {$to->label()}.");
        }

        return DB::transaction(function () use ($enquiry, $from, $to, $note) {
            $enquiry->status = $to;
            if ($to === EnquiryStatus::Lost) {
                $enquiry->lost_reason = $note;
            }
            $enquiry->save();

            EnquiryActivity::create([
                'enquiry_id' => $enquiry->id,
                'staff_id' => $enquiry->counsellor_id,
                'type' => 'status_change',
                'notes' => $note,
                'from_status' => $from->value,
                'to_status' => $to->value,
            ]);

            $this->audit->log('enquiry.status_changed', $enquiry,
                before: ['status' => $from->value],
                after: ['status' => $to->value],
            );

            return $enquiry;
        });
    }

    /**
     * Log a dated follow-up note and set the next follow-up date.
     */
    public function logActivity(Enquiry $enquiry, ?string $notes, ?string $nextFollowUp = null, string $type = 'note'): EnquiryActivity
    {
        return DB::transaction(function () use ($enquiry, $notes, $nextFollowUp, $type) {
            $activity = EnquiryActivity::create([
                'enquiry_id' => $enquiry->id,
                'staff_id' => $enquiry->counsellor_id,
                'type' => $type,
                'notes' => $notes,
                'next_follow_up_on' => $nextFollowUp,
            ]);

            if ($nextFollowUp) {
                $enquiry->update(['next_follow_up_on' => $nextFollowUp]);
            }

            return $activity;
        });
    }

    /**
     * Convert to a draft admission: creates the student (and guardian) plus a
     * provisional enrollment that Phase 5 completes, without re-keying. Blocks a
     * second conversion.
     */
    public function convert(Enquiry $enquiry): Enrollment
    {
        if ($enquiry->status === EnquiryStatus::Converted) {
            throw new DomainException('This enquiry is already converted.');
        }

        if (! $enquiry->course_id) {
            throw new DomainException('Set an interested course before converting.');
        }

        $sessionId = $enquiry->academic_session_id ?? active_session()?->id;

        return DB::transaction(function () use ($enquiry, $sessionId) {
            $student = Student::create([
                'institute_id' => $enquiry->institute_id,
                'branch_id' => $enquiry->branch_id,
                'name' => $enquiry->name,
                'phone' => $enquiry->phone,
                'email' => $enquiry->email,
            ]);

            if ($enquiry->guardian_name || $enquiry->guardian_phone) {
                $guardian = Guardian::create([
                    'institute_id' => $enquiry->institute_id,
                    'name' => $enquiry->guardian_name ?: 'Guardian',
                    'phone' => $enquiry->guardian_phone,
                ]);
                $student->guardians()->attach($guardian->id, ['is_primary' => true, 'relationship' => 'guardian']);
            }

            $enrollment = Enrollment::create([
                'institute_id' => $enquiry->institute_id,
                'branch_id' => $enquiry->branch_id,
                'student_id' => $student->id,
                'course_id' => $enquiry->course_id,
                'academic_session_id' => $sessionId,
                'enquiry_id' => $enquiry->id,
                'status' => 'provisional',
            ]);

            $from = $enquiry->status;
            $enquiry->update([
                'status' => EnquiryStatus::Converted,
                'converted_student_id' => $student->id,
            ]);

            EnquiryActivity::create([
                'enquiry_id' => $enquiry->id,
                'staff_id' => $enquiry->counsellor_id,
                'type' => 'status_change',
                'from_status' => $from->value,
                'to_status' => EnquiryStatus::Converted->value,
                'notes' => 'Converted to a provisional admission.',
            ]);

            $this->audit->log('enquiry.converted', $enquiry, after: [
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
            ]);

            return $enrollment;
        });
    }
}
