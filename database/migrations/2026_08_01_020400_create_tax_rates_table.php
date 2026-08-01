<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TaxRate - a GST rate applied to a fee component. Rate is stored in basis
 * points (1800 = 18.00%) so no floats enter the money path. The invoice engine
 * (Phase 7) splits this into CGST + SGST (in-state) or IGST (out-of-state).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');               // e.g. "GST 18%"
            $table->unsignedInteger('rate_bp');    // basis points, 1800 = 18.00%
            $table->string('hsn_sac', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
