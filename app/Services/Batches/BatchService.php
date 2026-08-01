<?php

namespace App\Services\Batches;

use App\Enums\EnrollmentStatus;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

class BatchService
{
    public function __construct(private AuditLogger $audit) {}

    /** Live enrollments currently assigned to a batch. */
    public function liveCount(Batch $batch): int
    {
        return Enrollment::withoutGlobalScopes()
            ->where('batch_id', $batch->id)
            ->whereIn('status', EnrollmentStatus::liveValues())
            ->count();
    }

    public function hasRoom(Batch $batch): bool
    {
        return $batch->capacity === 0 || $this->liveCount($batch) < $batch->capacity;
    }

    /**
     * Assign a student's enrollment to a batch, respecting capacity.
     */
    public function assign(Enrollment $enrollment, Batch $batch): Enrollment
    {
        if (! $this->hasRoom($batch)) {
            throw new DomainException("Batch {$batch->name} is at capacity ({$batch->capacity}).");
        }

        $enrollment->update(['batch_id' => $batch->id]);
        $this->audit->log('batch.assigned', $enrollment, after: ['batch_id' => $batch->id]);

        return $enrollment;
    }

    /**
     * Move a student to another batch, recording the reason.
     */
    public function move(Enrollment $enrollment, Batch $to, ?string $reason = null): Enrollment
    {
        if ((int) $enrollment->batch_id === $to->id) {
            return $enrollment;
        }

        if (! $this->hasRoom($to)) {
            throw new DomainException("Batch {$to->name} is at capacity ({$to->capacity}).");
        }

        return DB::transaction(function () use ($enrollment, $to, $reason) {
            $from = $enrollment->batch_id;
            $enrollment->update(['batch_id' => $to->id]);

            $this->audit->log('batch.moved', $enrollment,
                before: ['batch_id' => $from],
                after: ['batch_id' => $to->id, 'reason' => $reason],
            );

            return $enrollment;
        });
    }

    /**
     * Delete a batch only when no live students remain (move them first).
     */
    public function delete(Batch $batch): void
    {
        if ($this->liveCount($batch) > 0) {
            throw new DomainException('Move the batch\'s students before deleting it.');
        }

        $batch->delete();
    }
}
