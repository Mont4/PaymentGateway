<?php

namespace Mont4\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

class PaymentGateway extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'paymentgateway';
    }
}
