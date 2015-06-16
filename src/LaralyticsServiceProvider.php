<?php namespace Bsharp\Laralytics;

use Illuminate\Translation\TranslationServiceProvider;

/**
 * Class LaralyticsServiceProvider
 * @package Bsharp\Laralytics
 */
class LaralyticsServiceProvider extends TranslationServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        // Include Laralytics routes for API
        include __DIR__ . '/../publish/routes.php';


        /**
         * Register publish config
         */
        $this->publishes([
            __DIR__ . '/../publish/config/laralytics.php' => config_path('laralytics.php')
        ], 'config');

        /**
         * Register publish migrations
         */
        $this->publishes([
            __DIR__ . '/../publish/database/migrations/' => database_path('/migrations')
        ], 'migrations');

        /**
         * register publish middleware
         */
        $this->publishes([
            __DIR__ . '/../publish/Http/Middleware/' => app_path('/Http/Middleware')
        ], 'middleware');

        /**
         * register publish Eloquent model
         */
        $this->publishes([
            __DIR__ . '/../publish/Eloquent/' => app_path()
        ], 'eloquent');
    }

    public function register()
    {
        $this->app->bind('laralytics', function () {
            return new Laralytics();
        });
    }
}
