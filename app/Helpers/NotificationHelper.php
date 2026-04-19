<?php

namespace App\Helpers;

use App\Models\Notification\Notification;

class NotificationHelper
{
    public static function send(int $recipientId, int $senderId, string $type, array $payload = []): void
    {
        if ($recipientId === $senderId) return;

        Notification::create([
            'recipient_id' => $recipientId,
            'sender_id'    => $senderId,
            'type'         => $type,
            'payload'      => $payload,
        ]);
    }
}
