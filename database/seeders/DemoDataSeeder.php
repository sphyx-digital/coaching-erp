<?php

namespace Database\Seeders;

use App\Enums\EnquiryStatus;
use App\Models\Assessment;
use App\Models\AssessmentSubject;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\FeeComponent;
use App\Models\FeePlan;
use App\Models\GradeScale;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Admissions\AdmissionService;
use App\Services\Assessments\AssessmentService;
use App\Services\Attendance\AttendanceService;
use App\Services\Enquiries\EnquiryService;
use App\Services\Fees\FeeService;
use App\Services\Fees\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A realistic demo institute so dashboards and tables are populated. Idempotent:
 * skips if students already exist. Runs on every db:seed, so it also restores
 * the demo after a reset.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $institute = current_institute();
        if (! $institute || Student::count() > 0) {
            return;
        }

        $institute->update(['gstin' => '27ABACS6251R1ZS', 'city' => 'Indore', 'state_code' => '27', 'address' => '3rd Floor, MG Road']);
        $branch = Branch::firstOrCreate(['institute_id' => $institute->id, 'code' => 'MN'], ['name' => 'Main Centre', 'city' => 'Indore']);
        $session = active_session();
        Classroom::firstOrCreate(['branch_id' => $branch->id, 'code' => 'R1'], ['name' => 'Room 1', 'capacity' => 40]);

        $courses = collect(['JEE Foundation' => 'JEE', 'NEET Foundation' => 'NEET'])->map(
            fn ($code, $name) => Course::firstOrCreate(['institute_id' => $institute->id, 'code' => $code], ['name' => $name, 'duration_months' => 12])
        );
        $jee = $courses['JEE Foundation'];
        foreach (['Physics' => 'PHY', 'Chemistry' => 'CHE', 'Maths' => 'MAT'] as $sname => $scode) {
            Subject::firstOrCreate(['institute_id' => $institute->id, 'code' => $scode], ['name' => $sname, 'course_id' => $jee->id]);
        }

        $gst = TaxRate::firstOrCreate(['institute_id' => $institute->id, 'name' => 'GST 18%'], ['rate_bp' => 1800]);
        $plan = FeePlan::firstOrCreate(['institute_id' => $institute->id, 'name' => 'JEE Annual'], ['course_id' => $jee->id]);
        if ($plan->components()->count() === 0) {
            FeeComponent::create(['fee_plan_id' => $plan->id, 'tax_rate_id' => $gst->id, 'name' => 'Tuition', 'is_taxable' => true, 'amount' => 4000000]);
            FeeComponent::create(['fee_plan_id' => $plan->id, 'tax_rate_id' => $gst->id, 'name' => 'Registration', 'is_taxable' => true, 'amount' => 500000]);
            FeeComponent::create(['fee_plan_id' => $plan->id, 'name' => 'Material', 'is_taxable' => false, 'amount' => 300000]);
        }
        $plan->load('components.taxRate');

        $batch = Batch::firstOrCreate(
            ['branch_id' => $branch->id, 'academic_session_id' => $session->id, 'code' => 'JEE-A'],
            ['institute_id' => $institute->id, 'course_id' => $jee->id, 'name' => 'JEE Morning A', 'capacity' => 30]
        );

        $admissions = app(AdmissionService::class);
        $fees = app(FeeService::class);
        $payments = app(PaymentService::class);
        $attendance = app(AttendanceService::class);

        $names = ['Aarav Sharma', 'Diya Patel', 'Vivaan Gupta', 'Ananya Singh', 'Kabir Mehta', 'Ishita Rao'];
        $students = collect();
        foreach ($names as $i => $name) {
            $student = Student::create([
                'institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => $name,
                'dob' => now()->subYears(15)->subDays($i * 40)->toDateString(),
                'phone' => '90000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
            $g = Guardian::create(['institute_id' => $institute->id, 'name' => 'Parent of '.$name, 'phone' => '99000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
            $student->guardians()->attach($g->id, ['is_primary' => true, 'relationship' => 'guardian']);

            $enrollment = $admissions->enroll($student, $jee->id, $session->id, $branch->id, ['data', 'communication']);
            $enrollment->update(['batch_id' => $batch->id]);
            $students->push($student);

            // Invoice + a payment for most students (varied balances).
            $invoice = $fees->invoiceForPlan($enrollment->fresh(), $plan);
            if ($i % 3 !== 0) {
                $pay = $i % 2 === 0 ? (int) ($invoice->total) : (int) round($invoice->total / 2);
                $payments->record($student, $pay, 'upi', 'UPI'.$i, now()->subDays($i)->toDateString(), [$invoice->id => $pay]);
            }
        }

        // Attendance for the last 5 days.
        foreach (range(1, 5) as $d) {
            $sessionDate = now()->subDays($d)->toDateString();
            $sess = $attendance->openSession($batch, $sessionDate);
            $marks = [];
            foreach ($students as $idx => $st) {
                $marks[$st->id] = ($idx + $d) % 5 === 0 ? 'absent' : 'present';
            }
            $attendance->mark($sess, $marks);
        }

        // An assessment with marks + report cards.
        $assessment = Assessment::create([
            'institute_id' => $institute->id, 'branch_id' => $branch->id, 'batch_id' => $batch->id,
            'academic_session_id' => $session->id, 'name' => 'Unit Test 1', 'type' => 'test', 'assessment_date' => now()->subDays(3)->toDateString(),
        ]);
        $subjectRows = Subject::whereIn('code', ['PHY', 'CHE', 'MAT'])->get()->map(
            fn ($s) => AssessmentSubject::create(['assessment_id' => $assessment->id, 'subject_id' => $s->id, 'max_marks' => 100])
        );
        $svc = app(AssessmentService::class);
        $scale = GradeScale::where('is_active', true)->first();
        foreach ($students as $idx => $st) {
            foreach ($subjectRows as $j => $as) {
                $svc->enterMark($as, $st->id, 60 + (($idx * 7 + $j * 11) % 38));
            }
            $svc->generateReportCard($assessment, $st->id, $scale);
        }

        // Enquiries across the pipeline.
        $enq = app(EnquiryService::class);
        foreach (['New' => EnquiryStatus::New, 'Contacted' => EnquiryStatus::Contacted, 'Follow up' => EnquiryStatus::FollowUp, 'Visited' => EnquiryStatus::Visited] as $label => $status) {
            $e = $enq->create([
                'institute_id' => $institute->id, 'branch_id' => $branch->id, 'academic_session_id' => $session->id,
                'course_id' => $jee->id, 'name' => "Lead {$label}", 'phone' => '888800'.rand(1000, 9999), 'source' => 'walk-in',
            ]);
            if ($status !== EnquiryStatus::New) {
                $enq->transition($e, $status);
            }
            $enq->logActivity($e->fresh(), 'Follow up call', now()->toDateString());
        }

        // Role-based demo logins (click-to-fill on the sign-in page).
        $pw = Hash::make(config('client.demo_password', 'coaching123'));
        $makeStaff = function (string $email, string $name, string $role) use ($institute, $branch, $pw) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => $pw]);
            $u->syncRoles([$role]);
            Staff::firstOrCreate(['user_id' => $u->id], ['institute_id' => $institute->id, 'branch_id' => $branch->id, 'name' => $name]);

            return $u;
        };
        $makeStaff('counsellor@coaching.sphyx.in', 'Neha Counsellor', 'Counsellor');
        $makeStaff('teacher@coaching.sphyx.in', 'Rahul Teacher', 'Teacher');
        $makeStaff('accountant@coaching.sphyx.in', 'Priya Accountant', 'Accountant');

        // Parent demo login linked to the first demo student.
        $first = $students->first();
        if ($first) {
            $parentUser = User::firstOrCreate(['email' => 'parent@coaching.sphyx.in'], ['name' => 'Demo Parent', 'password' => $pw]);
            $parentUser->syncRoles(['Parent']);
            $g = Guardian::firstOrCreate(['institute_id' => $institute->id, 'user_id' => $parentUser->id], ['name' => 'Demo Parent', 'phone' => '9999900000']);
            $g->students()->syncWithoutDetaching([$first->id => ['is_primary' => true, 'relationship' => 'guardian']]);

            // Student demo login linked to the second demo student.
            $studentUser = User::firstOrCreate(['email' => 'student@coaching.sphyx.in'], ['name' => 'Demo Student', 'password' => $pw]);
            $studentUser->syncRoles(['Student']);
            ($students->get(1) ?? $first)->update(['user_id' => $studentUser->id]);
        }
    }
}
