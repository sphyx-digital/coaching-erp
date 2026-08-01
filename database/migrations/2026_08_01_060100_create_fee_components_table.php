<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FeeComponent - a line of a fee plan (tuition, registration, material), each
 * taxable or exempt with a GST rate. Amount in integer paise. Phase 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->boolean('is_taxable')->default(true);
            $table->paise('amount'); // integer paise
            $table->timestamps();
            $table->auditColumns();

            $table->index('fee_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_components');
    }
};
