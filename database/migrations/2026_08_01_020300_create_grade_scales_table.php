<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GradeScale - a configurable mapping of percentage ranges to grades, plus its
 * bands. Grade computation (Phase 9) reads the active scale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'is_active']);
        });

        Schema::create('grade_scale_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->cascadeOnDelete();
            // Percentage bounds stored in basis points (10000 = 100.00%) to avoid floats.
            $table->unsignedInteger('min_bp');
            $table->unsignedInteger('max_bp');
            $table->string('grade', 10);          // A+, A, B ...
            $table->unsignedSmallInteger('points')->nullable(); // grade points
            $table->timestamps();

            $table->index('grade_scale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_scale_bands');
        Schema::dropIfExists('grade_scales');
    }
};
