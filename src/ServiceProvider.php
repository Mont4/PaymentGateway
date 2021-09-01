<?php

namespace Mont4\PaymentGateway;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/payment-gateway.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('payment-gateway.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'payment-gateway'
        );

//        $this->app->bind('payment-gateway', function () {
//            return new PaymentGateway();
//        });
    }
}
