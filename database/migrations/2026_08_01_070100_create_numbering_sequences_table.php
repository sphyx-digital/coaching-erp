<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NumberingSequence - gapless sequential numbers per document type, per branch,
 * per session, with configurable prefixes. Incremented under a row lock (Phase
 * 3) so concurrent generation never duplicates or leaves gaps. Never edited by
 * hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('doc_type', 30); // admission | enquiry | invoice | receipt | refund
            $table->string('prefix', 20)->default('');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->timestamps();

            $table->unique(
                ['institute_id', 'branch_id', 'academic_session_id', 'doc_type'],
                'numbering_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
