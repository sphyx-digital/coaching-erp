<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scholarship - a student-level award applied to fees, with a reason and an
 * approver reference. Phase 7 (approval flow in Phase 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('kind', 10)->default('fixed'); // fixed | percent
            $table->paise('amount');
            $table->unsignedInteger('percent_bp')->default(0);
            $table->string('reason')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
