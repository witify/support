<?php

namespace Witify\Support\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NewNotification implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public mixed $notifiable) {}

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notifications.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel
    {
        $channel = (string) config('support.notifications.channel', 'user.{id}');

        return new PrivateChannel(str_replace('{id}', (string) $this->notifiable->getKey(), $channel));
    }
}
