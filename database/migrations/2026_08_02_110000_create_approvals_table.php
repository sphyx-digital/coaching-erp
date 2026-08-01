<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval requests. A request targets a record (polymorphic, nullable for a
 * proposed action), names the approver role, and records the decision. Every
 * request, decision and escalation is audited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('approvable_type')->nullable();
            $table->unsignedBigInteger('approvable_id')->nullable();

            $table->string('action', 60);          // fee.discount, fee.refund, enrollment.withdraw ...
            $table->string('title');
            $table->string('approver_role');        // role allowed to decide
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->bigInteger('amount')->nullable(); // context amount in paise
            $table->json('meta')->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();

            $table->timestamp('due_at')->nullable();     // SLA for escalation
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalated_to')->nullable();  // role escalated to

            $table->timestamps();
            $table->auditColumns();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['status', 'approver_role']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
