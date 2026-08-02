<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('effective_from');
            $table->unsignedBigInteger('monthly_gross');       // integer paise, = sum of earnings
            $table->json('earnings');                           // [{"name":"Basic","amount":...}, ...] paise
            $table->json('deductions')->nullable();             // fixed monthly deductions (PF, PT) in paise
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->index(['staff_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
