<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AssessmentSubject - a subject within an assessment with its maximum marks.
 * Marks are academic scores (decimals allowed), not money, so decimal is fine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('max_marks', 6, 2);
            $table->decimal('pass_marks', 6, 2)->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['assessment_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_subjects');
    }
};
