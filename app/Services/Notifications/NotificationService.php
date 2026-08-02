<?php

namespace App\Services\Notifications;

use App\Models\ConsentRecord;
use App\Models\MessageLog;
use Throwable;

/**
 * Channel-agnostic dispatch. Gates external channels by feature flag and by
 * communication consent, writes a MessageLog on every attempt (queued / sent /
 * skipped / failed), and supports retrying a failed message.
 */
class NotificationService
{
    public const CHANNELS = ['in_app', 'whatsapp', 'sms', 'email'];

    public function driver(string $channel): NotificationChannel
    {
        return match ($channel) {
            'in_app' => new InAppChannel,
            'email' => new EmailChannel,
            'whatsapp', 'sms' => new StubChannel($channel),
            default => new StubChannel($channel),
        };
    }

    /**
     * Dispatch a message on a channel and log it.
     *
     * @param  array<string,mixed>  $payload
     */
    public function dispatch(string $channel, array $payload): MessageLog
    {
        // External channels require their feature flag.
        if ($channel !== 'in_app' && ! feature($channel)) {
            return $this->log($channel, $payload, 'skipped', error: "channel {$channel} disabled");
        }

        // Communication consent gate for a named student.
        if ($channel !== 'in_app' && ! empty($payload['student_id'])) {
            $consented = ConsentRecord::where('student_id', $payload['student_id'])
                ->where('consent_type', 'communication')->where('granted', true)->exists();
            if (! $consented) {
                return $this->log($channel, $payload, 'skipped', error: 'no communication consent');
            }
        }

        try {
            $result = $this->driver($channel)->send($payload);
        } catch (Throwable $e) {
            return $this->log($channel, $payload, 'failed', error: $e->getMessage());
        }

        return $this->log($channel, $payload, $result['status'], $result['provider_ref'] ?? null, $result['error'] ?? null);
    }

    /** Retry a failed message on its channel. */
    public function retry(MessageLog $log): MessageLog
    {
        if ($log->status !== 'failed') {
            return $log;
        }

        return $this->dispatch($log->channel, array_merge($log->meta ?? [], [
            'recipient' => $log->recipient,
            'subject' => $log->subject,
            'body' => $log->body,
            'template_key' => $log->template_key,
            'institute_id' => $log->institute_id,
        ]));
    }

    public function toUser(int $userId, string $title, ?string $body = null, ?int $instituteId = null, ?array $meta = null): MessageLog
    {
        return $this->dispatch('in_app', [
            'user_id' => $userId, 'subject' => $title, 'body' => $body,
            'institute_id' => $instituteId, 'meta' => $meta,
        ]);
    }

    private function log(string $channel, array $payload, string $status, ?string $providerRef = null, ?string $error = null): MessageLog
    {
        return MessageLog::create([
            'institute_id' => $payload['institute_id'] ?? null,
            'channel' => $channel,
            'template_key' => $payload['template_key'] ?? null,
            'recipient' => $payload['recipient'] ?? ($payload['user_id'] ?? null),
            'subject' => $payload['subject'] ?? null,
            'body' => $payload['body'] ?? null,
            'status' => $status,
            'provider_ref' => $providerRef,
            'error' => $error,
            'meta' => $payload['meta'] ?? null,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
