<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardian - a parent or guardian. For a minor, one guardian is the mandatory
 * primary contact (enforced at enrollment in Phase 5). May link to a User for
 * the parent portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // portal login
            $table->string('name');
            $table->string('relation')->nullable(); // father, mother, guardian
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['institute_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
