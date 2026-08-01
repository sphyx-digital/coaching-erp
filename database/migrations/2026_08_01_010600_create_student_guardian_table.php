<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * StudentGuardian - links a student to a guardian with the relationship and a
 * primary flag. Ownership scoping for the parent portal (Phase 2/10) runs
 * through this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['student_id', 'guardian_id']);
            $table->index('guardian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardian');
    }
};
