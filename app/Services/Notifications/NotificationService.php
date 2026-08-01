<?php

namespace App\Services\Notifications;

use App\Models\MessageLog;

/**
 * Channel-agnostic dispatch. Resolves the channel driver, sends, and writes a
 * MessageLog row on every dispatch (including a queued state for stubbed
 * channels). Feeds the visible failure list in Phase 14.
 */
class NotificationService
{
    public const CHANNELS = ['in_app', 'whatsapp', 'sms', 'email'];

    public function driver(string $channel): NotificationChannel
    {
        return match ($channel) {
            'in_app' => new InAppChannel(),
            'whatsapp', 'sms', 'email' => new StubChannel($channel),
            default => new StubChannel($channel),
        };
    }

    /**
     * Dispatch a message on a channel and log it.
     *
     * @param  array<string,mixed>  $payload  recipient, subject, body, user_id, template_key, meta, institute_id
     */
    public function dispatch(string $channel, array $payload): MessageLog
    {
        $result = $this->driver($channel)->send($payload);

        return MessageLog::create([
            'institute_id' => $payload['institute_id'] ?? null,
            'channel' => $channel,
            'template_key' => $payload['template_key'] ?? null,
            'recipient' => $payload['recipient'] ?? ($payload['user_id'] ?? null),
            'subject' => $payload['subject'] ?? null,
            'body' => $payload['body'] ?? null,
            'status' => $result['status'],
            'provider_ref' => $result['provider_ref'] ?? null,
            'error' => $result['error'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'sent_at' => $result['status'] === 'sent' ? now() : null,
        ]);
    }

    /**
     * Convenience: send an in-app notification to a user.
     */
    public function toUser(int $userId, string $title, ?string $body = null, ?int $instituteId = null, ?array $meta = null): MessageLog
    {
        return $this->dispatch('in_app', [
            'user_id' => $userId,
            'subject' => $title,
            'body' => $body,
            'institute_id' => $instituteId,
            'meta' => $meta,
        ]);
    }
}
