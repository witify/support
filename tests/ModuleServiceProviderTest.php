<?php

namespace Witify\Support\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Witify\Support\SharedData\SharedData;
use Witify\Support\SupportServiceProvider;
use Witify\Support\Tests\Fixtures\Order;
use Witify\Support\Tests\Fixtures\OrderModuleServiceProvider;
use Witify\Support\Tests\Fixtures\OrderPolicy;
use Witify\Support\UserNotifications\UserNotifications;

class ModuleServiceProviderTest extends TestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SupportServiceProvider::class, OrderModuleServiceProvider::class];
    }

    public function test_a_module_registers_its_policies_and_user_notifications(): void
    {
        $this->assertInstanceOf(OrderPolicy::class, Gate::getPolicyFor(Order::class));

        /** @var UserNotifications $userNotifications */
        $userNotifications = app(SupportServiceProvider::USER_NOTIFICATIONS);

        $this->assertSame('Order shipped', $userNotifications->all()['modules.orders.shipped']->label);
    }

    public function test_the_shared_data_registry_merges_values_and_lazy_callbacks(): void
    {
        /** @var SharedData $sharedData */
        $sharedData = app(SupportServiceProvider::SHARED_DATA);

        $sharedData->put('app.locale', 'fr');
        $sharedData->put('modules.orders', fn (): array => ['statuses' => ['open']]);
        $sharedData->put('modules.empty', fn (): array => []);

        $this->assertSame('fr', $sharedData->get('app.locale'));
        $this->assertSame([
            'app' => ['locale' => 'fr'],
            'modules' => ['orders' => ['statuses' => ['open']]],
        ], $sharedData->all());
    }
}
