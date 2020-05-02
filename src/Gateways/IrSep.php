<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Support\Str;
use SoapClient;

class IrSep implements GatewayInterface
{
    const VERIFY_STATUS = [
        -111 => "ساختار صحیح نمی‌باشد.",
        -18  => "IP شما برای این ترمینال ثبت نشده است.",
        -6   => "زمان تایید درخواست به پایان رسیده است.",
        -1   => "کدفروشنده یا RefNum صحیح نمی‌باشد.",
        -20  => "مبلغ دریافتی از بانک با سند تراکنش تطابق ندارد. پول به حساب شما برمی‌گردد.",
    ];

    private $apiKey;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;
    private $password;

    public function __construct($config)
    {
        $this->apiKey     = $config['api_key'];
        $this->password   = $config['password'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->verifyUrl  = $config['verify_url'];
        $this->redirect   = $config['redirect'];
    }

    public function request(int $amount, string $mobile = NULL, string $factorNumber = NULL, string $description = NULL)
    {
        if ($amount < 1000)
            throw new \Exception('amount is lower than 1000');

        if (!$factorNumber)
            $factorNumber = "sep_" . Str::random(40);

        return [
            'success'     => true,
            'method'      => 'post',
            'gateway_url' => $this->gatewayUrl,
            'token'       => $factorNumber,
            'data'        => [
                'Amount'      => $amount,
                'CellNumber'  => $mobile,
                'MID'         => $this->apiKey,
                'ResNum'      => $factorNumber,
                'RedirectURL' => $this->redirect,
            ],
        ];
    }

    public function verify($RefNum, ?int $amount = NULL)
    {
        try {
            $soapClient = new SoapClient($this->verifyUrl);
            $response   = $soapClient->verifyTransaction($RefNum, $this->apiKey);

            if ($response < 0) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response] ?? NULL,
                ];
            }

            if ($response != $amount) {
                // Reverse Money
                $this->reverse($RefNum, $response);

                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[-20] ?? NULL,
                ];
            }

            return [
                'success' => true,
            ];
        } catch (\Exception $ex) {
            \Log::error($ex);
        }

        return [
            'success' => false,
        ];
    }

    public function reverse($RefNum, $amount)
    {
        try {
            $soapClient = new SoapClient($this->verifyUrl);
            $response   = $soapClient->reverseTransaction($RefNum, $this->apiKey, $this->password, $amount);

            if ($response < 0) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response] ?? NULL,
                ];
            }

            return [
                'success' => true,
            ];
        } catch (\Exception $ex) {
            \Log::error($ex);
        }

        return [
            'success' => false,
        ];
    }
}
