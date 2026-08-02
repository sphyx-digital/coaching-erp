<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress | submitted | auto_submitted
            $table->integer('score')->default(0);          // may be negative with negative marking
            $table->unsignedInteger('max_score')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('unanswered_count')->default(0);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['exam_id', 'student_id']); // one attempt per student per exam
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
