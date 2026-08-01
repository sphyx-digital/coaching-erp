<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NotificationTemplate - a stored, channel-agnostic message template rendered
 * by the notification core (Phase 3) and dispatched over in-app, WhatsApp, SMS
 * or email drivers (Phase 14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);           // fee_reminder, receipt, result ...
            $table->string('channel', 20)->default('in_app'); // in_app|whatsapp|sms|email
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->auditColumns();

            $table->unique(['institute_id', 'key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
