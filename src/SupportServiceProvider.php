<?php

namespace Witify\Support;

use Illuminate\Support\ServiceProvider;
use Witify\Support\SharedData\SharedData;
use Witify\Support\UserNotifications\UserNotifications;

class SupportServiceProvider extends ServiceProvider
{
    public const SHARED_DATA = 'config::sharedData';

    public const USER_NOTIFICATIONS = 'config::userNotifications';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/support.php', 'support');
        $this->mergeConfigFrom(__DIR__ . '/../config/html-sanitizer.php', 'html-sanitizer');

        $this->app->singleton(self::SHARED_DATA, fn (): SharedData => new SharedData);
        $this->app->singleton(self::USER_NOTIFICATIONS, fn (): UserNotifications => new UserNotifications);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'support');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/support.php' => config_path('support.php'),
                __DIR__ . '/../config/html-sanitizer.php' => config_path('html-sanitizer.php'),
            ], 'support-config');

            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath() . '/vendor/support',
            ], 'support-translations');
        }
    }
}
