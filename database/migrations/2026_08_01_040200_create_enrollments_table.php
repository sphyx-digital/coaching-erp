<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrollment - a student enrolled into a course for a session. The fee-bearing
 * record (Phase 7). Holds a nullable batch reference set in Phase 6. A duplicate
 * ACTIVE enrollment for the same student, course and session is prevented in
 * application logic (Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete(); // provenance
            $table->string('status', 20)->default('provisional'); // provisional|active|on_hold|completed|withdrawn
            $table->date('enrolled_on')->nullable();
            $table->date('withdrawn_on')->nullable();
            $table->string('withdraw_reason')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['branch_id', 'academic_session_id', 'status']);
            $table->index(['student_id', 'course_id', 'academic_session_id']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
