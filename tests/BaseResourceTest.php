<?php

namespace Witify\Support\Tests;

use Illuminate\Support\Facades\Gate;
use Witify\Support\Tests\Fixtures\Order;
use Witify\Support\Tests\Fixtures\OrderPolicy;
use Witify\Support\Tests\Fixtures\OrderResource;

class BaseResourceTest extends TestCase
{
    public function test_it_appends_the_view_update_delete_permissions_of_the_authenticated_user(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        $order = Order::query()->create(['number' => 'ORD-1']);

        $this->actingAs($this->user('Ada'));
        $array = (new OrderResource($order))->toArray(request());

        $this->assertSame('ORD-1', $array['number']);
        $this->assertSame(['view' => true, 'update' => true, 'delete' => false], $array['can']);

        $this->actingAs($this->user('Grace'));

        $this->assertSame(['view' => true, 'update' => false, 'delete' => false], (new OrderResource($order))->toArray(request())['can']);
    }
}
