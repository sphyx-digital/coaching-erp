<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mark - one student's score for one assessment subject. A null score means
 * "not entered" (never treated as zero until confirmed). Phase 9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('marks_obtained', 6, 2)->nullable(); // null = not entered
            $table->boolean('is_absent')->default(false);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['assessment_subject_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
