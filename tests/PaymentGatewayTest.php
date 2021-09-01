<?php

namespace Mont4\PaymentGateway\Tests;

use Mont4\PaymentGateway\Facades\PaymentGateway;
use Mont4\PaymentGateway\ServiceProvider;
use Orchestra\Testbench\TestCase;

class PaymentGatewayTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'payment-gateway' => PaymentGateway::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
