<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:42 PM
 */

namespace Mont4\PaymentGateway\Gateways;


interface GatewayInterface
{
    public function request();

    public function verify($token, ?int $amount = NULL);
}
