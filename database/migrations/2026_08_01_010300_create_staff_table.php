<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff - the employee profile linked one to one with a User login. Teachers,
 * counsellors, accountants, and admins all have a Staff row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // primary branch
            $table->string('employee_code', 30)->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('designation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'employee_code']);
            $table->index(['branch_id', 'is_active']);
        });

        // A staff member may be assigned to more than one branch (Phase 2 scoping).
        Schema::create('branch_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_staff');
        Schema::dropIfExists('staff');
    }
};
