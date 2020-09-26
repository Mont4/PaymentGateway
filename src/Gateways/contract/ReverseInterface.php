<?php


namespace Mont4\PaymentGateway\Gateways;


interface ReverseInterface
{
    public function reverse($RefNum, $amount);
}
