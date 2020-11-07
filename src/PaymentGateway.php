<?php

namespace Mont4\PaymentGateway;

use Illuminate\Http\Request;
use Mont4\PaymentGateway\Gateways\IrPay;
use Mont4\PaymentGateway\Gateways\IrSep;
use Mont4\PaymentGateway\Gateways\IrSeppay;
use Mont4\PaymentGateway\Gateways\IrTop;
use Mont4\PaymentGateway\Gateways\IrFanavacard;

/**
 * Class PaymentGateway
 *
 * @package Mont4\PaymentGateway
 *
 * @method self setAmount(int $amount)
 * @method self setMobile(string $mobile)
 * @method self setOrderId(int $orderId)
 * @method self setRequest(Request $request)
 *
 * @method string getToken($key)
 * @method string getResponseBy($key)
 * @method array getResponse()
 *
 * @method request()
 * @method verify()
 * @method reverse($token)
 */
class PaymentGateway
{
    const IR_PAY        = 'ir_pay';
    const IR_SEP        = 'ir_sep';
    const IR_SEP_PAY    = 'ir_sep_pay';
    const IR_TOP        = 'ir_top';
    const IR_FANAVACARD = 'ir_fanavacard';

    const GATEWAYS = [
        self::IR_PAY,
        self::IR_SEP,
        self::IR_SEP_PAY,
        self::IR_TOP,
        self::IR_FANAVACARD,
    ];

    const GATEWAY_CLASSES = [
        self::IR_PAY        => IrPay::class,
        self::IR_SEP        => IrSep::class,
        self::IR_SEP_PAY    => IrSeppay::class,
        self::IR_TOP        => IrTop::class,
        self::IR_FANAVACARD => IrFanavacard::class,
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
