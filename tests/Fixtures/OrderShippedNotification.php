<?php

namespace Witify\Support\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Witify\Support\Mail\MailMessage;
use Witify\Support\NotificationChannels\DatabaseChannel;

class OrderShippedNotification extends Notification
{
    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage($notifiable))->line('Your order ' . $this->order->number . ' shipped.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'text' => 'Order ' . $this->order->number . ' shipped',
            'model' => $this->order,
            'url' => 'https://app.example.test/admin/orders/' . $this->order->id,
            'path' => '/orders/' . $this->order->id,
        ];
    }
}
