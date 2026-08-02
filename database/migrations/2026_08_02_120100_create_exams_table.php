<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedTinyInteger('pass_percentage')->default(33);
            $table->unsignedInteger('total_marks')->default(0); // cached sum of question marks
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('negative_marking')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft'); // draft | published | closed
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
