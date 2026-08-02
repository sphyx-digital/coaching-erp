<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Find or create the attendance session for a batch, date and optional slot.
     * Marking the same session twice updates it - it never duplicates (unique
     * index on batch + date + slot).
     */
    public function openSession(Batch $batch, string $date, ?int $slotId = null): AttendanceSession
    {
        // Explicit whereNull — firstOrCreate with a null attribute generates
        // "= NULL" which never matches, causing duplicate sessions.
        $existing = AttendanceSession::where('batch_id', $batch->id)
            ->whereDate('session_date', $date)
            ->when($slotId === null, fn ($q) => $q->whereNull('timetable_slot_id'), fn ($q) => $q->where('timetable_slot_id', $slotId))
            ->first();

        return $existing ?? AttendanceSession::create([
            'batch_id' => $batch->id,
            'session_date' => $date,
            'timetable_slot_id' => $slotId,
            'status' => 'open',
        ]);
    }

    /** Live-enrolled student ids in a batch (the roster). */
    public function rosterIds(Batch $batch): array
    {
        return Enrollment::withoutGlobalScopes()
            ->where('batch_id', $batch->id)
            ->whereIn('status', EnrollmentStatus::liveValues())
            ->pluck('student_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Mark (or re-mark) a roster. A student not enrolled in the batch cannot be
     * marked. Edits to a finalised session are audited with before/after.
     *
     * @param  array<int,string>  $statuses  studentId => status value
     */
    public function mark(AttendanceSession $session, array $statuses): void
    {
        $roster = $this->rosterIds($session->batch);

        foreach (array_keys($statuses) as $studentId) {
            if (! in_array((int) $studentId, $roster, true)) {
                throw new DomainException("Student {$studentId} is not enrolled in this batch.");
            }
        }

        $wasFinalised = $session->status === 'finalised';
        $before = $wasFinalised
            ? AttendanceRecord::where('attendance_session_id', $session->id)->pluck('status', 'student_id')->all()
            : [];

        DB::transaction(function () use ($session, $statuses) {
            foreach ($statuses as $studentId => $status) {
                AttendanceRecord::updateOrCreate(
                    ['attendance_session_id' => $session->id, 'student_id' => $studentId],
                    ['status' => AttendanceStatus::from($status)->value],
                );
            }
        });

        if ($wasFinalised) {
            $this->audit->log('attendance.edited', $session, before: $before, after: $statuses);
        }
    }

    /** Finalise a session. A future-dated session cannot be finalised. */
    public function finalize(AttendanceSession $session): AttendanceSession
    {
        if ($session->session_date->isAfter(now()->startOfDay())) {
            throw new DomainException('A future-dated session cannot be finalised.');
        }

        $session->update(['status' => 'finalised']);

        return $session;
    }

    /**
     * Attendance summary for a student, optionally within a batch and date range.
     *
     * @return array{present:int, total:int, percent_bp:int}
     */
    public function studentSummary(int $studentId, ?int $batchId = null, ?string $from = null, ?string $to = null): array
    {
        $rows = $this->records($batchId, $from, $to)->where('attendance_records.student_id', $studentId)
            ->pluck('attendance_records.status')->map(fn ($s) => AttendanceStatus::from($s));

        return $this->summarise($rows);
    }

    public function batchPercentBp(int $batchId, ?string $from = null, ?string $to = null): int
    {
        $rows = $this->records($batchId, $from, $to)->pluck('attendance_records.status')->map(fn ($s) => AttendanceStatus::from($s));

        return $this->summarise($rows)['percent_bp'];
    }

    /**
     * Students in a batch below the low-attendance threshold (basis points).
     *
     * @return array<int,int> studentId => percent_bp
     */
    public function lowAttendance(int $batchId, ?int $thresholdBp = null): array
    {
        $thresholdBp ??= (int) client_setting('low_attendance_threshold_bp', 7500);
        $studentIds = $this->records($batchId)->distinct()->pluck('attendance_records.student_id');

        $low = [];
        foreach ($studentIds as $studentId) {
            $pct = $this->studentSummary((int) $studentId, $batchId)['percent_bp'];
            if ($pct < $thresholdBp) {
                $low[(int) $studentId] = $pct;
            }
        }

        return $low;
    }

    private function records(?int $batchId, ?string $from = null, ?string $to = null)
    {
        return AttendanceRecord::query()
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->when($batchId, fn ($q) => $q->where('attendance_sessions.batch_id', $batchId))
            ->when($from, fn ($q) => $q->whereDate('attendance_sessions.session_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('attendance_sessions.session_date', '<=', $to));
    }

    /**
     * @param  Collection<int,AttendanceStatus>  $statuses
     * @return array{present:int, total:int, percent_bp:int}
     */
    private function summarise(Collection $statuses): array
    {
        $present = $statuses->filter(fn ($s) => $s->countsAsPresent())->count();
        $total = $statuses->filter(fn ($s) => $s->inDenominator())->count();
        $percentBp = $total > 0 ? (int) round($present / $total * 10000) : 0;

        return ['present' => $present, 'total' => $total, 'percent_bp' => $percentBp];
    }
}
