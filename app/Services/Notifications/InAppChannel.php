<?php

namespace App\Services\Notifications;

use App\Models\Notification;

/**
 * The in-app driver: writes a Notification row for the recipient user.
 */
class InAppChannel implements NotificationChannel
{
    public function send(array $message): array
    {
        if (empty($message['user_id'])) {
            return ['status' => 'failed', 'error' => 'in-app notification requires a user_id'];
        }

        $notification = Notification::create([
            'institute_id' => $message['institute_id'] ?? null,
            'user_id' => $message['user_id'],
            'type' => $message['template_key'] ?? null,
            'title' => $message['subject'] ?? 'Notification',
            'body' => $message['body'] ?? null,
            'data' => $message['meta'] ?? null,
        ]);

        return ['status' => 'sent', 'provider_ref' => (string) $notification->id];
    }
}
