<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ReportCard - a generated card per student per assessment (or session), a
 * versioned snapshot. Republishing supersedes the prior version and keeps
 * history. Percentages stored in basis points to avoid floats. Phase 9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('max_total', 8, 2)->default(0);
            $table->unsignedInteger('percentage_bp')->default(0); // 8750 = 87.50%
            $table->string('overall_grade', 10)->nullable();
            $table->unsignedInteger('attendance_bp')->nullable(); // attendance % in basis points
            $table->json('payload')->nullable(); // frozen subject/mark/grade snapshot
            $table->string('status', 20)->default('draft'); // draft | published | superseded
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['student_id', 'assessment_id', 'version']);
            $table->index(['assessment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
