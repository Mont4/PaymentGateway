<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:40 PM
 */

namespace Mont4\PaymentGateway\Gateways;

use Mont4\PaymentGateway\PaymentGateway;

class IrPay implements GatewayInterface
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
        $this->redirect   = $config['redirect'];
    }

    public function request(int $amount, string $mobile = NULL, string $factorNumber = NULL, string $description = NULL)
    {
        if ($amount < 1000)
            throw new \Exception('amount is lower than 1000');

        try {
            $body = [
                'api'          => $this->apiKey,
                'amount'       => $amount,
                'mobile'       => $mobile,
                'factorNumber' => $factorNumber,
                'description'  => $description,
                'redirect'     => $this->redirect . "?gateway=" . PaymentGateway::IR_PAY,
            ];

            // request to pay.ir for token
            $response = $this->curl_post($this->requestUrl, $body);
            if (!$response)
                return [
                    'status' => false,
                ];

            $response = json_decode($response);
            if ($response->status) {
                $gatewayUrl = "{$this->gatewayUrl}/{$response->token}";

                return [
                    'status'      => true,
                    'token'       => $response->token,
                    'gateway_url' => $gatewayUrl,
                ];
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
        }


        return [
            'status' => false,
        ];

    }

    public function verify($token)
    {
        try {
            $response = $this->curl_post($this->verifyUrl, [
                'api'   => $this->apiKey,
                'token' => $token,
            ]);
            if (!$response)
                return [
                    'status' => false,
                ];

            $response = json_decode($response);
            if ($response->status == 1) {
                return [
                    'status'         => true,
                    'transaction_id' => $response->transId,
                    'factor_number'  => $response->factorNumber,
                    'mobile'         => $response->mobile,
                    'description'    => $response->description,
                    'card_number'    => $response->cardNumber,
                    'message'        => $response->message,
                ];
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
        }

        return [
            'status' => false,
        ];

    }

    private function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}
