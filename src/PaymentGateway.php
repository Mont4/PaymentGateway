<?php

namespace Mont4\PaymentGateway;

use Mont4\PaymentGateway\Gateways\IrPay;
use Mont4\PaymentGateway\Gateways\IrSep;
use Mont4\PaymentGateway\Gateways\IrSeppay;
use Mont4\PaymentGateway\Gateways\IrTop;

/**
 * Class PaymentGateway
 *
 * @package Mont4\PaymentGateway
 *
 * @method setAmount(int $amount)
 * @method setMobile(string $mobile)
 * @method setOrderId(int $orderId)
 * @method request(int $amount, string $mobile = NULL, string $factorNumber = NULL, string $description = NULL)
 * @method verify($token, $amount = NULL)
 * @method reverse($token)
 */
class PaymentGateway
{
    const IR_PAY     = 'ir_pay';
    const IR_SEP     = 'ir_sep';
    const IR_SEP_PAY = 'ir_sep_pay';
    const IR_TOP     = 'ir_top';

    const GATEWAYS = [
        self::IR_PAY,
        self::IR_SEP,
        self::IR_SEP_PAY,
        self::IR_TOP,
    ];

    const GATEWAY_CLASSES = [
        self::IR_PAY     => IrPay::class,
        self::IR_SEP     => IrSep::class,
        self::IR_SEP_PAY => IrSeppay::class,
        self::IR_TOP     => IrTop::class,
    ];

    private $gateway = NULL;
    private $config  = [];
    private $sender;

    /**
     * SmsService constructor.
     */
    private function __construct($gateway)
    {
        $this->gateway = $gateway;

        $this->config = config("payment_gateway.gateways.{$gateway}");
        if (!$this->config) {
            throw new \Exception("Gateway config is not exists.");
        }
    }

    static public function gateway($gateway)
    {
        return new self($gateway);
    }

    public function __call($name, $arguments)
    {
        if (!in_array($this->config['gateway'], self::GATEWAYS)) {
            throw new \Exception('Gateway is not exists.');
        }

        // class from gateway name
        $gateway = new \ReflectionClass(self::GATEWAY_CLASSES[$this->config['gateway']]);

        // construct class of gateway
        $gateway = $gateway->newInstanceArgs([$this->config]);

        // check called method exist
        if (!method_exists($gateway, $name)) {
            throw new \Exception('Method is not exists.');
        }

        // call method of gateway
        return $gateway->{$name}(...$arguments);
    }
}
