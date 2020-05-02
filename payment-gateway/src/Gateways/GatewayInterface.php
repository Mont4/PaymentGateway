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
    public function request(int $amount, string $mobile = NULL, string $factorNumber = NULL, string $description = NULL);

    public function verify($token, ?int $amount = NULL);
}
