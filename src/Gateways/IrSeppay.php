<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Support\Str;
use SoapClient;

class IrSeppay extends PaymentAbstract implements GatewayInterface
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
        $this->gatewayUrl = 'seppay://%s/%s/%d';
        $this->verifyUrl  = 'https://api.seppay.ir/1/verify';
        $this->redirect   = $config['redirect'];
    }

    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function request()
    {
        if (!$this->orderId)
            $this->orderId = "sep_pay_" . Str::random(40);

        $gatewayUrl = sprintf($this->gatewayUrl, $this->orderId, $this->amount);

        return [
            'success'     => true,
            'method'      => 'get',
            'gateway_url' => $gatewayUrl,
            'token'       => $this->orderId,
        ];
    }

    public function verify($RefNum, ?int $amount = NULL)
    {
        try {
            $response = $this->curlPost($this->verifyUrl, [
                'mobile_no' => $this->mobile,
                'ref_no'    => $RefNum,
            ]);

            \Log::info($response);

            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if (!$response->ResponseCode) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response->errorCode],
                ];

            }

            if ($response->ResponseCode == 1) {
                return [
                    'success'        => true,
                    'message'        => $response->message,
                    'transaction_id' => $response->transId,
                    'factor_number'  => $response->factorNumber,
                    'mobile'         => $response->mobile,
                    'description'    => $response->description,
                    'card_number'    => $response->cardNumber,
                ];
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
        }

        return [
            'success' => false,
            'message' => 'خطایی رخ داده است.',
        ];
    }
}
