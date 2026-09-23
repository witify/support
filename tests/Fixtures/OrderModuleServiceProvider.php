<?php

namespace Witify\Support\Tests\Fixtures;

use Witify\Support\ModuleServiceProvider;
use Witify\Support\UserNotifications\UserNotification;

class OrderModuleServiceProvider extends ModuleServiceProvider
{
    protected $policies = [
        Order::class => OrderPolicy::class,
    ];

    public function name(): string
    {
        return 'orders';
    }

    public function sharedData(): array
    {
        return ['statuses' => ['open', 'closed']];
    }

    public function userNotifications(): array
    {
        return ['shipped' => new UserNotification('Order shipped')];
    }
}
