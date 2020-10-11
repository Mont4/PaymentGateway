<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:40 PM
 */

namespace Mont4\PaymentGateway\Gateways;

use Mont4\PaymentGateway\PaymentGateway;

class IrPay extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [
        -1 => "ارسال api الزامی می باشد,",
        -2 => "ارسال transId الزامی می باشد,",
        -3 => "درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد,",
        -4 => "فروشنده غیر فعال می باشد,",
        -5 => "تراکنش با خطا مواجه شده است,",
    ];

    private $apiKey;
    private $requestUrl;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;

    public function __construct($config)
    {
        $this->apiKey     = $config['api_key'];
        $this->requestUrl = $config['request_url'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->verifyUrl  = $config['verify_url'];
        $this->redirect   = $config['redirect'] . "?gateway=" . PaymentGateway::IR_PAY;
    }

    public function request()
    {
        try {
            $body = [
                'api' => $this->apiKey,

                'amount'       => $this->amount,
                'mobile'       => $this->mobile,
                'factorNumber' => $this->orderId,
                'description'  => $this->description,

                'redirect' => $this->redirect,
            ];

            // request to pay.ir for token
            $response = $this->curl_post($this->requestUrl, $body);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->status) {
                $gatewayUrl = "{$this->gatewayUrl}/{$response->token}";

                return [
                    'success'     => true,
                    'method'      => 'get',
                    'gateway_url' => $gatewayUrl,
                    'token'       => $response->token,
                    'data'        => [
                        'Amount'      => $this->amount,
                        'RedirectURL' => $gatewayUrl,
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

    public function verify($token, ?int $amount = NULL)
    {
        try {
            $response = $this->curl_post($this->verifyUrl, [
                'api'   => $this->apiKey,
                'token' => $token,
            ]);
            \Log::info($response);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if (!$response->status) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response->errorCode],
                ];

            }

            if ($response->status == 1) {
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
