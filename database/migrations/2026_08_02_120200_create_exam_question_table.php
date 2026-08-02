<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedSmallInteger('marks')->nullable(); // override the question's default marks
            $table->timestamps();

            $table->unique(['exam_id', 'question_id']);
            $table->index(['exam_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question');
    }
};
