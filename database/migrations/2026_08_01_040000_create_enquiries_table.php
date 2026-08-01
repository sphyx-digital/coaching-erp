<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enquiry - a lead in the counsellor pipeline (Phase 4). Converts to an
 * admission draft. Enquiry number is unique per institute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete(); // interested course
            $table->foreignId('counsellor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('enquiry_number', 40)->nullable();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('source')->nullable(); // walk-in, referral, online ...
            $table->string('status', 20)->default('new'); // new|contacted|follow_up|visited|converted|lost
            $table->string('lost_reason')->nullable();
            $table->date('next_follow_up_on')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'enquiry_number']);
            $table->index(['branch_id', 'status']);
            $table->index(['counsellor_id', 'next_follow_up_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
