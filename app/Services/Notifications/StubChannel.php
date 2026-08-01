<?php

namespace App\Services\Notifications;

/**
 * Placeholder for a channel wired to a real provider in Phase 14 (WhatsApp, SMS,
 * email). It never fails the caller: it accepts the message and reports a queued
 * state, which the MessageLog records.
 */
class StubChannel implements NotificationChannel
{
    public function __construct(private string $channel) {}

    public function send(array $message): array
    {
        return ['status' => 'queued', 'provider_ref' => null];
    }
}
