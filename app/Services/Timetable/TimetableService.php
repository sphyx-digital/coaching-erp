<?php

namespace App\Services\Timetable;

use App\Models\TimetableSlot;
use App\Services\Audit\AuditLogger;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Timetable scheduling with server-side conflict checks. Two slots on the same
 * day conflict when their time ranges overlap: existing.start < new.end AND
 * existing.end > new.start.
 */
class TimetableService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{batch_id:int,teacher_id:?int,classroom_id:?int,day_of_week:int,start_time:string,end_time:string}  $data
     * @return array<string> conflict messages (empty = no conflict)
     */
    public function conflicts(array $data, ?int $ignoreId = null): array
    {
        $start = $this->normalize($data['start_time']);
        $end = $this->normalize($data['end_time']);

        $overlap = fn () => TimetableSlot::query()
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));

        $messages = [];

        // A batch cannot have two subjects in the same slot.
        if ((clone $overlap())->where('batch_id', $data['batch_id'])->exists()) {
            $messages[] = 'This batch already has a class in an overlapping slot.';
        }

        // A teacher cannot be double-booked.
        if (! empty($data['teacher_id']) && (clone $overlap())->where('teacher_id', $data['teacher_id'])->exists()) {
            $messages[] = 'The teacher is already booked in an overlapping slot.';
        }

        // A room cannot be double-booked.
        if (! empty($data['classroom_id']) && (clone $overlap())->where('classroom_id', $data['classroom_id'])->exists()) {
            $messages[] = 'The room is already booked in an overlapping slot.';
        }

        return $messages;
    }

    /**
     * Add a slot after checking conflicts inside the save transaction.
     */
    public function addSlot(array $data): TimetableSlot
    {
        return DB::transaction(function () use ($data) {
            $conflicts = $this->conflicts($data);
            if ($conflicts) {
                throw new DomainException(implode(' ', $conflicts));
            }

            $slot = TimetableSlot::create($data);
            $this->audit->log('timetable.slot_added', $slot, after: $data);

            return $slot;
        });
    }

    public function removeSlot(TimetableSlot $slot): void
    {
        $this->audit->log('timetable.slot_removed', $slot, before: $slot->getOriginal());
        $slot->delete();
    }

    /** Normalise HH:MM (or any parseable time) to HH:MM:SS for correct comparison. */
    private function normalize(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
