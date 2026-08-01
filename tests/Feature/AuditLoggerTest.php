<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Institute;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sensitive_update_records_before_and_after(): void
    {
        $this->actingAs(User::factory()->create());
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'A', 'code' => 'A']);
        $student = Student::create(['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Old Name']);

        $original = $student->getOriginal();
        $student->name = 'New Name';
        $student->save();

        $entry = app(AuditLogger::class)->logChange('student.updated', $student, $original);

        $this->assertSame('New Name', $entry->after['name']);
        $this->assertSame('Old Name', $entry->before['name']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.updated', 'auditable_id' => $student->id]);
    }

    public function test_deleting_an_auditable_model_is_logged(): void
    {
        $this->actingAs(User::factory()->create());
        $institute = Institute::create(['name' => 'Acme']);
        $branch = Branch::create(['institute_id' => $institute->id, 'name' => 'Gone', 'code' => 'GN']);

        $branch->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Branch.deleted',
            'auditable_id' => $branch->id,
        ]);
    }
}
