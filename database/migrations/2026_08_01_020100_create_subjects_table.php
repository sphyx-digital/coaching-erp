<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subject - a subject taught within a course (for example Physics).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'code']);
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
