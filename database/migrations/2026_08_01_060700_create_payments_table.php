<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment - SoT for a receipt. Offline (cash, cheque, UPI reference, bank) at
 * launch; the online gateway posts through the same path in Phase 14. Receipt
 * number is unique per institute (series) and gapless (Phase 3 numbering).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 40)->nullable();
            $table->date('payment_date');
            $table->string('mode', 20)->default('cash'); // cash|cheque|upi|bank|online
            $table->string('reference')->nullable();      // cheque no, UPI ref, txn id
            $table->paise('amount');
            $table->paise('allocated');   // sum of allocations
            $table->paise('unallocated'); // amount - allocated (advance)
            $table->string('status', 20)->default('completed'); // completed | reversed
            // Online gateway fields (Phase 14), kept nullable now.
            $table->string('gateway')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'receipt_number']);
            $table->index(['branch_id', 'payment_date']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
