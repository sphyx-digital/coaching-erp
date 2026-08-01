<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RefundReason - a configurable reason a refund can cite. Phase 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_reasons');
    }
};
