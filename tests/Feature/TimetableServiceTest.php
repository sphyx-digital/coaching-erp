<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Institute;
use App\Models\Staff;
use App\Models\User;
use App\Services\Timetable\TimetableService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private Branch $branch;
    private Batch $batchA;
    private Batch $batchB;
    private Staff $teacher;
    private Classroom $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institute = Institute::create(['name' => 'Acme']);
        $this->branch = Branch::create(['institute_id' => $this->institute->id, 'name' => 'Main', 'code' => 'MN']);
        $course = Course::create(['institute_id' => $this->institute->id, 'name' => 'JEE', 'code' => 'JEE']);
        $session = AcademicSession::create(['institute_id' => $this->institute->id, 'name' => '2026-27', 'is_active' => true]);
        $this->teacher = Staff::create(['user_id' => User::factory()->create()->id, 'institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'name' => 'Mr T']);
        $this->room = Classroom::create(['branch_id' => $this->branch->id, 'name' => 'Room 1', 'code' => 'R1']);

        $common = ['institute_id' => $this->institute->id, 'branch_id' => $this->branch->id, 'course_id' => $course->id, 'academic_session_id' => $session->id];
        $this->batchA = Batch::create($common + ['name' => 'A', 'code' => 'A']);
        $this->batchB = Batch::create($common + ['name' => 'B', 'code' => 'B']);
    }

    private function service(): TimetableService
    {
        return app(TimetableService::class);
    }

    private function slot(Batch $batch, string $start, string $end, ?int $teacher = null, ?int $room = null): array
    {
        return [
            'batch_id' => $batch->id, 'teacher_id' => $teacher, 'classroom_id' => $room,
            'day_of_week' => 1, 'start_time' => $start, 'end_time' => $end,
        ];
    }

    public function test_a_teacher_cannot_be_double_booked(): void
    {
        $this->service()->addSlot($this->slot($this->batchA, '09:00', '10:00', teacher: $this->teacher->id));

        $this->expectException(DomainException::class);
        // Different batch, overlapping time, same teacher.
        $this->service()->addSlot($this->slot($this->batchB, '09:30', '10:30', teacher: $this->teacher->id));
    }

    public function test_a_room_cannot_be_double_booked(): void
    {
        $this->service()->addSlot($this->slot($this->batchA, '09:00', '10:00', room: $this->room->id));

        $this->expectException(DomainException::class);
        $this->service()->addSlot($this->slot($this->batchB, '09:30', '10:30', room: $this->room->id));
    }

    public function test_a_batch_cannot_have_two_overlapping_subjects(): void
    {
        $this->service()->addSlot($this->slot($this->batchA, '09:00', '10:00'));

        $this->expectException(DomainException::class);
        $this->service()->addSlot($this->slot($this->batchA, '09:30', '10:30'));
    }

    public function test_non_overlapping_slots_are_allowed(): void
    {
        $this->service()->addSlot($this->slot($this->batchA, '09:00', '10:00', teacher: $this->teacher->id, room: $this->room->id));
        // Same teacher and room, but a later, non-overlapping slot.
        $slot = $this->service()->addSlot($this->slot($this->batchA, '10:00', '11:00', teacher: $this->teacher->id, room: $this->room->id));

        $this->assertNotNull($slot->id);
        $this->assertDatabaseCount('timetable_slots', 2);
    }
}
