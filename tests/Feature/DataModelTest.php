<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institute;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    private function baseGraph(): array
    {
        $institute = Institute::create(['name' => 'Acme Coaching']);
        $branch = Branch::create([
            'institute_id' => $institute->id, 'name' => 'Main', 'code' => 'MN',
        ]);
        $session = AcademicSession::create([
            'institute_id' => $institute->id, 'name' => '2026-27', 'is_active' => true,
        ]);
        $student = Student::create([
            'institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => 'Riya',
        ]);
        $course = Course::create([
            'institute_id' => $institute->id, 'name' => 'JEE Foundation', 'code' => 'JEE',
        ]);

        return compact('institute', 'branch', 'session', 'student', 'course');
    }

    public function test_core_entities_create_and_read(): void
    {
        ['institute' => $i, 'branch' => $b, 'session' => $s, 'student' => $st, 'course' => $c] = $this->baseGraph();

        $enrollment = Enrollment::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $st->id,
            'course_id' => $c->id, 'academic_session_id' => $s->id, 'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $st->id,
            'invoice_number' => 'INV/26-27/0001', 'invoice_date' => '2026-08-01',
            'subtotal' => 1000000, 'total' => 1180000, 'balance' => 1180000,
        ]);

        $payment = Payment::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $st->id,
            'receipt_number' => 'RCPT/26-27/0001', 'payment_date' => '2026-08-01',
            'amount' => 500000,
        ]);

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'active']);
        $this->assertSame(1180000, $invoice->fresh()->total); // money is integer paise
        $this->assertSame('Riya', $st->enrollments()->first()->student->name);
        $this->assertSame(500000, $payment->fresh()->amount);
    }

    public function test_duplicate_admission_number_is_rejected(): void
    {
        ['institute' => $i, 'branch' => $b] = $this->baseGraph();

        Student::create(['institute_id' => $i->id, 'branch_id' => $b->id, 'name' => 'A', 'admission_number' => 'ADM001']);

        $this->expectException(QueryException::class);
        Student::create(['institute_id' => $i->id, 'branch_id' => $b->id, 'name' => 'B', 'admission_number' => 'ADM001']);
    }

    public function test_duplicate_receipt_number_is_rejected(): void
    {
        ['institute' => $i, 'branch' => $b, 'student' => $st] = $this->baseGraph();

        Payment::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $st->id,
            'receipt_number' => 'RCPT001', 'payment_date' => '2026-08-01', 'amount' => 100,
        ]);

        $this->expectException(QueryException::class);
        Payment::create([
            'institute_id' => $i->id, 'branch_id' => $b->id, 'student_id' => $st->id,
            'receipt_number' => 'RCPT001', 'payment_date' => '2026-08-01', 'amount' => 200,
        ]);
    }
}
