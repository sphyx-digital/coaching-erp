<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LedgerEntry - a light double-entry ledger for fee postings. Every posting and
 * payment writes balanced entries (debits equal credits) grouped by a batch
 * reference, so the books reconcile and reversals are compensating, never
 * destructive (Phase 7 and Phase 12). Paise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->string('group_ref', 40)->index(); // ties both legs of a transaction
            $table->string('account', 40);            // fees_receivable, cash, tax_payable ...
            $table->paise('debit');
            $table->paise('credit');
            $table->string('narration')->nullable();
            // Polymorphic source (invoice, payment, refund ...)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['source_type', 'source_id']);
            $table->index(['institute_id', 'account']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
