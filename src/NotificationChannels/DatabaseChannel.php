<?php

namespace Witify\Support\NotificationChannels;

use Illuminate\Notifications\Notification;
use Witify\Support\Events\NewNotification;

class DatabaseChannel
{
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notification, 'toDatabase')) {
            throw new \Exception('Notification is missing toDatabase method.');
        }

        /**
         * @disregard P1013
         */
        $data = $notification->toDatabase($notifiable);

        $model = $data['model'] ?? null;

        $payload = [
            'id' => $notification->id,
            'text' => $data['text'],
            'model_id' => $model->id ?? null,
            'model_type' => $model ? get_class($model) : null,
            'url' => $data['url'] ?? null,
            'path' => $data['path'] ?? null,
            'type' => get_class($notification),
        ];

        $notification = $notifiable
            ->routeNotificationFor('database')
            ->create($payload);

        if (config('support.notifications.broadcast', true)) {
            broadcast(new NewNotification($notifiable));
        }

        return $notifiable;
    }
}
