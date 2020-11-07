<?php


namespace Mont4\PaymentGateway\Gateways\Contract;


interface ReverseInterface
{
    public function reverse($RefNum, $amount);
}
