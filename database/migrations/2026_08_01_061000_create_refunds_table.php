<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund - a refund against a payment, with a reason. A refund cannot exceed
 * the received amount, and advance may be non-refundable where the client
 * configures it (Phase 7). Paise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refund_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_number', 40)->nullable();
            $table->date('refund_date');
            $table->paise('amount');
            $table->string('mode', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 20)->default('completed'); // completed | reversed
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'refund_number']);
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
