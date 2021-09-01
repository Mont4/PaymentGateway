<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;

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


    public function __construct($config)
    {
        $this->apiKey = $config['api_key'];

        $this->username = $config['username'];
        $this->password = $config['password'];

        $this->authorization = base64_encode("$this->username:$this->password");

        $this->requestUrl = 'https://merchantapi.top.ir/api/EShop/GetToken';
        $this->gatewayUrl = 'https://app.top.ir';
        $this->verifyUrl  = 'https://merchantapi.top.ir/api/EShop/Confirm';

        $this->redirect = $config['callback_url'];
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
            $response = $this->curlPost($this->requestUrl, $body, [
                "Authorization : Basic $this->authorization",
            ]);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->Status == 0) {
                return [
                    'success'     => true,
                    'method'      => 'post',
                    'gateway_url' => $this->gatewayUrl,
                    'token'       => $response->Data->Token,
                    'data'        => [
                        'Amount'   => $this->amount,
                        'Token'    => $response->Data->Token,
                        'Callback' => $this->redirect,
                        'Pin'      => $this->apiKey,
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

    public function verify()
    {
        $body = [
            'Token' => $this->getToken(),
        ];

        try {
            $response = $this->curlPost($this->verifyUrl, $body, [
                "Authorization : Basic $this->authorization",
            ]);

            \Log::info($response);

            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->Status != 0) {
                return [
                    'success' => false,
                    'status'  => $response->Status,
                    'message' => $response->Message,
                ];

            }

            $this->data['trace_number'] = $response->Data->InvoiceNumber;

            return [
                'success'      => true,
                'trace_number' => $response->Data->InvoiceNumber,
            ];
        } catch (\Exception $ex) {
            \Log::error($ex);
        }

        return [
            'success' => false,
            'message' => 'خطایی رخ داده است.',
        ];
    }

    public function setRequest(Request $request)
    {
        $requestData = $request->all();
        \Log::info($requestData);

        $status = false;
        if (isset($requestData['TransactionStatus'])) {
            $status = $requestData['TransactionStatus'] == '0';
        }

        $this->data = [
            'status' => $status,

            'mid'   => $requestData['MID'] ?? NULL,
            'token' => $requestData['Token'] ?? NULL,


            'reserve_number'            => NULL,
            'reference_number'          => $requestData['ReferenceCode'] ?? NULL,
            'trace_number'              => NULL,
            'customer_reference_number' => NULL,
            'transaction_amount'        => NULL,

            'card_hashed' => NULL,
            'card_number' => NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
