<?php

namespace Witify\Support\Tests;

use Illuminate\Support\Facades\Event;
use Witify\Support\Events\NewNotification;
use Witify\Support\Tests\Fixtures\Order;
use Witify\Support\Tests\Fixtures\OrderShippedNotification;

class DatabaseChannelTest extends TestCase
{
    public function test_it_stores_the_notification_row_and_broadcasts_to_the_user_channel(): void
    {
        Event::fake([NewNotification::class]);
        $user = $this->user();
        $order = Order::query()->create(['number' => 'ORD-1']);

        $user->notify(new OrderShippedNotification($order));

        $this->assertDatabaseHas('notifications', [
            'type' => OrderShippedNotification::class,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'model_type' => Order::class,
            'model_id' => $order->id,
            'text' => 'Order ORD-1 shipped',
            'url' => 'https://app.example.test/admin/orders/' . $order->id,
            'path' => '/orders/' . $order->id,
        ]);

        Event::assertDispatched(NewNotification::class, function (NewNotification $event) use ($user): bool {
            return $event->notifiable->is($user)
                && $event->broadcastAs() === 'notifications.created'
                && $event->broadcastOn()->name === 'private-user.' . $user->id;
        });
    }

    public function test_the_channel_name_and_the_broadcast_are_configurable(): void
    {
        Event::fake([NewNotification::class]);
        config()->set('support.notifications.channel', 'App.Models.User.{id}');
        $user = $this->user();

        $this->assertSame('private-App.Models.User.' . $user->id, (new NewNotification($user))->broadcastOn()->name);

        config()->set('support.notifications.broadcast', false);
        $user->notify(new OrderShippedNotification(Order::query()->create(['number' => 'ORD-2'])));

        $this->assertDatabaseCount('notifications', 1);
        Event::assertNotDispatched(NewNotification::class);
    }
}
