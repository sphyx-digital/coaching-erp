<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * InvoiceLine - one component on an invoice with its own taxable value and GST
 * split. Rounding is per line and deterministic; lines sum to the invoice
 * total to the paisa. Phase 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('tax_rate_bp')->default(0); // 1800 = 18%
            $table->paise('taxable_value');
            $table->paise('cgst');
            $table->paise('sgst');
            $table->paise('igst');
            $table->paise('line_total');
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
