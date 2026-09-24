<?php

namespace Witify\Support\Tests;

use Illuminate\Database\Eloquent\Model;
use Witify\Support\Model\IsResource;
use Witify\Support\Model\IsResourceTrait;
use Witify\Support\Tests\Fixtures\Order;

class IsResourceTraitTest extends TestCase
{
    public function test_resource_data_carries_the_seven_keys_the_frontend_reads(): void
    {
        $order = Order::query()->create(['number' => 'ORD-1', 'client_name' => 'Acme']);

        $this->assertSame([
            'title' => 'ORD-1',
            'subtitle' => 'Acme',
            'icon' => 'heroicons:shopping-bag-16-solid',
            'color' => 'blue',
            'admin_to' => 'orders/' . $order->id,
            'admin_url' => url('/admin/orders/' . $order->id),
            'model_type' => Order::class,
        ], $order->resource_data);

        $this->assertArrayHasKey('resource_data', $order->toArray());
    }

    public function test_the_admin_url_follows_the_configured_prefix(): void
    {
        config()->set('support.admin_url', '/backoffice/');
        $order = Order::query()->create(['number' => 'ORD-1']);

        $this->assertSame(url('/backoffice/orders/' . $order->id), $order->getResourceAdminUrl());
    }

    public function test_the_shared_data_gives_the_icon_and_color_of_the_model(): void
    {
        $this->assertSame(['icon' => 'heroicons:shopping-bag-16-solid', 'color' => 'blue'], Order::getResourceSharedData());
    }

    public function test_a_model_without_its_own_color_is_gray(): void
    {
        $model = new class extends Model implements IsResource
        {
            use IsResourceTrait;

            public function getResourceTitle(): string
            {
                return 'Untitled';
            }

            public function getResourceSubtitle(): ?string
            {
                return null;
            }

            public static function getResourceIcon(): string
            {
                return 'heroicons:cube-16-solid';
            }

            public function getResourceAdminTo(): string
            {
                return 'things/1';
            }
        };

        $this->assertSame(['icon' => 'heroicons:cube-16-solid', 'color' => 'gray'], $model::getResourceSharedData());
        $this->assertSame('gray', $model->resource_data['color']);
    }
}
