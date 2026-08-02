<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('mcq'); // mcq (single correct) for now
            $table->text('body');
            $table->json('options');          // [{"key":"A","text":"..."}, ...]
            $table->string('correct_option', 8); // matches an option key
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('marks')->default(1);
            $table->unsignedSmallInteger('negative_marks')->default(0);
            $table->string('difficulty', 10)->nullable(); // easy | medium | hard
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'course_id']);
            $table->index(['institute_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
