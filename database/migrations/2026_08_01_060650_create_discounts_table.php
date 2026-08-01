<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount - a reduction at plan or invoice level, with a reason and an
 * approver reference (approval flow lands in Phase 11). Percent stored in
 * basis points; fixed amount in paise. Phase 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('kind', 10)->default('fixed'); // fixed | percent
            $table->paise('amount');                 // used when kind = fixed
            $table->unsignedInteger('percent_bp')->default(0); // used when kind = percent
            $table->string('reason')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
