<?php

namespace App\Services\Notifications;

/**
 * A delivery channel. The in-app channel is implemented now; WhatsApp, SMS and
 * email are stubbed and wired to real providers in Phase 14. A channel never
 * throws to the caller: a stubbed channel returns a queued result.
 *
 * @return array{status:string, provider_ref?:?string, error?:?string}
 */
interface NotificationChannel
{
    /**
     * @param  array<string,mixed>  $message  recipient, subject, body, user_id, meta
     * @return array{status:string, provider_ref?:?string, error?:?string}
     */
    public function send(array $message): array;
}
