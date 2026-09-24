<?php

namespace Witify\Support\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Gate;
use Witify\Support\Tests\Fixtures\Order;
use Witify\Support\Tests\Fixtures\OrderPolicy;

class BaseQueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Order::class, OrderPolicy::class);

        Order::query()->create(['number' => 'ORD-1', 'client_name' => 'Acme', 'shipped_at' => '2026-01-10 10:00:00']);
        Order::query()->create(['number' => 'ORD-2', 'client_name' => 'Globex', 'shipped_at' => '2026-01-20 10:00:00']);
        Order::query()->create(['number' => 'ORD-3', 'client_name' => 'Initech', 'shipped_at' => '2026-02-01 10:00:00']);
    }

    public function test_protect_refuses_guests_and_users_without_view_any(): void
    {
        $this->expectException(AuthenticationException::class);

        Order::query()->protect();
    }

    public function test_protect_names_the_unauthorized_action_in_the_package_translation(): void
    {
        $this->actingAs($this->user('Guest'));

        try {
            Order::query()->protect();
            $this->fail('An AuthorizationException was expected.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('Unauthorized action', $exception->getMessage());
        }

        $this->actingAs($this->user('Ada'));

        $this->assertCount(3, Order::query()->protect()->get());
    }

    public function test_search_matches_columns_expressions_and_callbacks_case_insensitively(): void
    {
        request()->merge(['search' => 'GLOB']);

        $this->assertSame(['ORD-2'], Order::query()->search(['client_name'])->pluck('number')->all());
        $this->assertSame(['ORD-2'], Order::query()->search([new Expression('client_name')])->pluck('number')->all());
        $this->assertSame(['ORD-3'], Order::query()->search([
            fn ($query, string $search) => $query->orWhere('number', 'ORD-3'),
        ])->pluck('number')->all());

        request()->merge(['search' => '  ']);

        $this->assertCount(3, Order::query()->search(['client_name'])->get());
    }

    public function test_search_treats_the_keyword_as_a_literal(): void
    {
        Order::query()->create(['number' => 'ORD-4', 'client_name' => '100% Cotton']);
        Order::query()->create(['number' => 'ORD-5', 'client_name' => 'a_b']);
        Order::query()->create(['number' => 'ORD-6', 'client_name' => 'axb']);

        request()->merge(['search' => '100%']);
        $this->assertSame(['ORD-4'], Order::query()->search(['client_name'])->pluck('number')->all());

        request()->merge(['search' => '%']);
        $this->assertSame(['ORD-4'], Order::query()->search(['client_name', new Expression('number')])->pluck('number')->all());

        request()->merge(['search' => 'a_b']);
        $this->assertSame(['ORD-5'], Order::query()->search(['client_name'])->pluck('number')->all());
    }

    public function test_search_keeps_the_builder_when_a_callback_returns_nothing(): void
    {
        request()->merge(['search' => 'acme']);

        $this->assertSame(['ORD-1'], Order::query()->search([
            function ($query, string $search): void {
                $query->orWhere('client_name', 'like', $search);
            },
        ])->pluck('number')->all());
    }

    public function test_date_between_accepts_one_day_or_a_range(): void
    {
        $this->assertSame(['ORD-1'], Order::query()->dateBetween('shipped_at', ['2026-01-10'])->pluck('number')->all());
        $this->assertSame(['ORD-1', 'ORD-2'], Order::query()->dateBetween('shipped_at', ['2026-01-01', '2026-01-31'])->pluck('number')->all());
        $this->assertCount(3, Order::query()->dateBetween('shipped_at', null)->get());
    }

    public function test_pagination_adds_the_primary_key_as_a_tie_breaker(): void
    {
        $paginator = Order::query()->orderByDesc('client_name')->paginate(2);

        $this->assertSame(['ORD-3', 'ORD-2'], $paginator->getCollection()->pluck('number')->all());
        $this->assertStringContainsString('order by "client_name" desc, "orders"."id" desc', $paginator->getCollection()->isEmpty() ? '' : Order::query()->orderByDesc('client_name')->orderByDesc('orders.id')->toSql());
    }

    public function test_exclude_drops_columns_from_the_select(): void
    {
        $order = Order::query()->exclude('notes', 'client_name')->first();

        $this->assertNotNull($order);
        $this->assertArrayHasKey('number', $order->getAttributes());
        $this->assertArrayNotHasKey('notes', $order->getAttributes());
        $this->assertArrayNotHasKey('client_name', $order->getAttributes());
    }
}
