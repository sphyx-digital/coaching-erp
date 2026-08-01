<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice - SoT for a fee charge. GST split into CGST + SGST (in-state) or IGST
 * (out-of-state). All money in integer paise; the sum of lines equals the total
 * to the paisa (Phase 7). Invoice number is unique per institute (series).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number', 40)->nullable();
            $table->date('invoice_date');
            $table->string('place_of_supply_code', 2)->nullable(); // GST state code
            $table->boolean('is_interstate')->default(false);
            $table->paise('subtotal');        // taxable value before tax
            $table->paise('discount_total');
            $table->paise('cgst_total');
            $table->paise('sgst_total');
            $table->paise('igst_total');
            $table->paise('tax_total');
            $table->paise('total');           // grand total
            $table->paise('amount_paid');
            $table->paise('balance');
            $table->string('status', 20)->default('draft'); // draft|issued|partial|paid|cancelled
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'invoice_number']);
            $table->index(['branch_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
