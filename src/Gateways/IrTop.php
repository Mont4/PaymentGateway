<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Support\Str;

class IrTop extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [
        -111 => "ساختار صحیح نمی‌باشد.",
        -18  => "IP شما برای این ترمینال ثبت نشده است.",
        -6   => "زمان تایید درخواست به پایان رسیده است.",
        -1   => "کدفروشنده یا RefNum صحیح نمی‌باشد.",
        -20  => "مبلغ دریافتی از بانک با سند تراکنش تطابق ندارد. پول به حساب شما برمی‌گردد.",
    ];

    private $apiKey;

    private $requestUrl;
    private $gatewayUrl;
    private $verifyUrl;

    private $redirect;

    private $username;
    private $password;

    private $authorization;

    private $mobile;

    public function __construct($config)
    {
        $this->apiKey = $config['api_key'];

        $this->username = $config['username'];
        $this->password = $config['password'];

        $this->authorization = base64_encode("$this->username:$this->password");

        $this->requestUrl = 'https://merchantapi.top.ir/api/EShop/GetToken';
        $this->gatewayUrl = 'seppay://%s/%s/%d';
        $this->verifyUrl  = 'https://merchantapi.top.ir/api/EShop/Confirm';

        $this->redirect = $config['redirect'];
    }

    public function request()
    {
        try {
            $body = [
                'Pin' => $this->apiKey,

                'Amount'   => $this->amount,
                'MobileNo' => $this->mobile,
                'OrderId'  => $this->orderId,
            ];

            // request to pay.ir for token
            $response = $this->curl_post($this->requestUrl, $body);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->Status == 0) {
                $gatewayUrl = "{$this->gatewayUrl}/{$response->token}";

                return [
                    'success'     => true,
                    'method'      => 'post',
                    'gateway_url' => $gatewayUrl,
                    'token'       => $response->Data->Token,
                    'data'        => [
                        'Amount'   => $this->amount,
                        'Token'    => $response->Data->Token,
                        'Callback' => $this->redirect,
                    ],
                ];
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
        }


        return [
            'success' => false,
        ];
    }

    public function verify($RefNum, ?int $amount = NULL)
    {
        try {
            $response = $this->curl_post($this->verifyUrl, [
                'Token' => $RefNum,
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
                    'transaction_id' => $response->InvoiceNumber,
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
