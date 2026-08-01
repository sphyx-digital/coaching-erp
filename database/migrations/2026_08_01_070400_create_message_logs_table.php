<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MessageLog - a row written on every dispatch through the notification core,
 * across every channel, including a queued state for stubbed channels. Feeds
 * the visible failure list in Phase 14.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);          // in_app|whatsapp|sms|email
            $table->string('template_key', 60)->nullable();
            $table->string('recipient')->nullable(); // phone / email / user id
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('queued'); // queued|sent|failed
            $table->string('provider_ref')->nullable();
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
