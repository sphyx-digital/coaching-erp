<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FeeSchedule - a dated installment of a fee plan for one enrollment. Phase 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('installment_no')->default(1);
            $table->date('due_on')->nullable();
            $table->paise('amount');
            $table->string('status', 20)->default('pending'); // pending | invoiced | paid | waived
            $table->timestamps();
            $table->auditColumns();

            $table->index(['enrollment_id', 'status']);
            $table->index('due_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
    }
};
