<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AttendanceSession - one attendance sitting for a batch on a date (and slot,
 * where a batch meets more than once a day). Phase 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timetable_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date');
            $table->string('status', 20)->default('open'); // open | finalised
            $table->foreignId('marked_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
            $table->auditColumns();

            // One session per batch, date and slot - prevents duplicate rosters.
            $table->unique(['batch_id', 'session_date', 'timetable_slot_id'], 'attendance_session_unique');
            $table->index(['batch_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
