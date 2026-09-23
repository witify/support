<?php

namespace Witify\Support;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Witify\Support\UserNotifications\UserNotification;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Route paths
     *
     * @var array<number, string>
     */
    protected $routes = [];

    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Language folder path
     *
     * @var string
     */
    protected $lang = null;

    /**
     * Indicates if the view data has been shared.
     */
    private bool $viewDataShared = false;

    /**
     * Get the module name
     */
    abstract public function name(): string;

    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function policies()
    {
        return $this->policies;
    }

    /**
     * Get the routes defined on the provider.
     *
     * @return array<number, string>
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * Get the data to share with the client.
     *
     * @return array<string, mixed>
     */
    public function sharedData(): array
    {
        return [];
    }

    /**
     * Get the user notifications.
     *
     * @return array<string, UserNotification>
     */
    public function userNotifications(): array
    {
        return [];
    }

    public function register(): void
    {
        $this->registerRoutes();

        $this->registerSharedData();

        $this->registerUserNotifications();

        $this->booting(function () {

            $this->registerTranslations();

            $this->registerPolicies();
        });
    }

    /**
     * Register the application's routes.
     *
     * @return void
     */
    public function registerRoutes()
    {
        foreach ($this->routes() as $route) {
            $this->loadRoutesFrom($route);
        }
    }

    /**
     * Register the application's view data.
     *
     * @return void
     */
    private function registerSharedData()
    {
        // Wil get called every time a view is rendered
        View::composer('*', function () {

            // Only share data once

            if ($this->viewDataShared) {
                return;
            }

            $this->viewDataShared = true;

            $callback = function () {
                return $this->sharedData();
            };

            $key = 'modules.' . $this->name();

            app('config::sharedData')->put($key, $callback);
        });
    }

    /**
     * Register the application's user notifications.
     *
     * @return void
     */
    private function registerUserNotifications()
    {
        foreach ($this->userNotifications() as $key => $userNotification) {
            $key = 'modules.' . $this->name() . '.' . $key;
            app('config::userNotifications')->push($key, $userNotification);
        }
    }

    /**
     * Register the application's translations.
     *
     * @return void
     */
    private function registerTranslations()
    {
        if (! $this->lang) {
            return;
        }

        $this->loadTranslationsFrom(
            $this->lang,
            $this->name()
        );
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
