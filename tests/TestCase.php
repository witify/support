<?php

namespace Witify\Support\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Witify\Support\SupportServiceProvider;
use Witify\Support\Tests\Fixtures\User;

abstract class TestCase extends TestbenchTestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SupportServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.name', 'Client Example');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->text('notes')->nullable();
            $table->string('client_name')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->nullableMorphs('model');
            $table->text('text');
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    protected function user(string $firstName = 'Ada', string $password = 'secret'): User
    {
        return User::query()->create([
            'first_name' => $firstName,
            'last_name' => 'Lovelace',
            'email' => strtolower($firstName) . '@example.test',
            'password' => bcrypt($password),
        ]);
    }
}
