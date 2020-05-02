<?php

namespace Mont4\PaymentGateway;

use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'mont4');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'mont4');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/payment_gateway.php', 'payment_gateway');

        // Register the service the package provides.
//        $this->app->singleton('paymentgateway', function ($app) {
//            return new PaymentGateway;
//        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['paymentgateway'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
			__DIR__ . '/../config/payment_gateway.php' => config_path('paymentgateway.php'),
        ], 'paymentgateway.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/mont4'),
        ], 'paymentgateway.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/mont4'),
        ], 'paymentgateway.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/mont4'),
        ], 'paymentgateway.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
