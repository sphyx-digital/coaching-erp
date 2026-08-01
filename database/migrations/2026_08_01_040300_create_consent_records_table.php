<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ConsentRecord - guardian consent captured at enrollment (data and
 * communication), with a timestamp and the consenting guardian. Gates
 * messaging in Phase 14.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('consent_type', 30); // data | communication
            $table->boolean('granted')->default(false);
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->auditColumns();

            $table->index(['student_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
