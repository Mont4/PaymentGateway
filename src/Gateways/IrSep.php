<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SoapClient;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;

class IrSep extends PaymentAbstract implements GatewayInterface
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
        $this->initUrl    = $config['init_url'];
        $this->verifyUrl  = $config['verify_url'];
        $this->redirect   = $config['redirect'];
    }

    public function request()
    {
        if (!$this->orderId)
            $this->orderId = "sep_" . microtime(true) . '_' . Str::random(24);

        try {
            $soapClient = new SoapClient($this->initUrl);
            $token      = $soapClient->RequestToken($this->apiKey, $this->orderId, $this->amount, $this->redirect);

            if ($token < 0) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$token] ?? NULL,
                ];
            }

            return [
                'success'     => true,
                'method'      => 'post',
                'gateway_url' => $this->gatewayUrl,
                'token'       => $this->orderId,
                'data'        => [
                    'Token'       => $token,
                    'Amount'      => $this->amount,
                    'CellNumber'  => $this->mobile,
                    'MID'         => $this->apiKey,
                    'ResNum'      => $this->orderId,
                    'RedirectURL' => $this->redirect,
                ],
            ];
        } catch (\Exception $ex) {
            \Log::error($ex);
        }


        return [
            'success' => false,
        ];
    }

    public function verify()
    {
        $RefNum = $this->getResponseBy('reference_number');

        try {
            $soapClient = new SoapClient($this->verifyUrl);
            $response   = $soapClient->VerifyTransaction($RefNum, $this->apiKey);

            if ($response < 0) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response] ?? NULL,
                ];
            }

            if ($response != $this->amount) {
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

    public function setRequest(Request $request)
    {
        $requestData = $request->all();

        $status = false;
        if (isset($requestData['State'])) {
            $status = $requestData['State'] == 'OK';
        }

        $this->data = [
            'status' => $status,

            'mid'   => $requestData['MID'] ?? NULL,
            'token' => $requestData['ResNum'] ?? NULL,


            'reserve_number'            => $requestData['ResNum'] ?? NULL,
            'reference_number'          => $requestData['RefNum'] ?? NULL,
            'trace_number'              => $requestData['TraceNo'] ?? NULL,
            'customer_reference_number' => NULL,
            'transaction_amount'        => $requestData['Amount'] ?? NULL,

            'card_hashed' => $requestData['HashedCardNumber'] ?? NULL,
            'card_number' => $requestData['SecurePan'] ?? NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
