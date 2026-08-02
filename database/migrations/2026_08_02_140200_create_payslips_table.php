<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->date('month'); // first day of the pay month
            $table->unsignedSmallInteger('days_in_month');
            $table->decimal('unpaid_days', 5, 1)->default(0);   // half-day weighted
            $table->unsignedBigInteger('gross');                 // paise
            $table->unsignedBigInteger('lop_amount')->default(0); // loss of pay (paise)
            $table->unsignedBigInteger('fixed_deductions')->default(0); // paise
            $table->unsignedBigInteger('net');                   // paise
            $table->json('earnings');
            $table->json('deductions')->nullable();
            $table->string('status', 20)->default('draft'); // draft | finalized | paid
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['staff_id', 'month']);
            $table->index(['institute_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
