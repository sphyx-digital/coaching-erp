<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Mail;

/**
 * Email driver via the configured mailer (log/array in dev, SMTP in production).
 */
class EmailChannel implements NotificationChannel
{
    public function send(array $message): array
    {
        $to = $message['recipient'] ?? null;
        if (! $to) {
            return ['status' => 'failed', 'error' => 'email requires a recipient'];
        }

        Mail::raw((string) ($message['body'] ?? ''), function ($mail) use ($to, $message) {
            $mail->to($to)->subject($message['subject'] ?? 'Notification');
        });

        return ['status' => 'sent', 'provider_ref' => null];
    }
}
