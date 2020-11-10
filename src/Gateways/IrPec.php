<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SoapClient;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;

class IrPec extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [
        -111 => "ساختار صحیح نمی‌باشد.",
        -18  => "IP شما برای این ترمینال ثبت نشده است.",
        -6   => "زمان تایید درخواست به پایان رسیده است.",
        -1   => "کدفروشنده یا RefNum صحیح نمی‌باشد.",
        -20  => "مبلغ دریافتی از بانک با سند تراکنش تطابق ندارد. پول به حساب شما برمی‌گردد.",
    ];

    private $pin;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;
    private $password;

    public function __construct($config)
    {
        $this->pin        = $config['pin'];
        $this->password   = $config['password'];
        $this->gatewayUrl = "https://pec.shaparak.ir/NewIPG/?Token=%s";
        $this->initUrl    = "https://pec.shaparak.ir/NewIPGServices/MultiplexedSale/OnlineMultiplexedSalePaymentService.asmx?WSDL";
        $this->verifyUrl  = "https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL";
        $this->redirect   = $config['redirect'];
    }

    public function request()
    {
        if (!$this->orderId)
            $this->orderId = "pec_" . microtime(true) . '_' . Str::random(24);

        $params = [
            "LoginAccount"     => $this->pin,
            "Amount"           => $this->amount,
            "OrderId"          => $this->orderId,
            "CallBackUrl"      => $this->redirect,
            "MultiplexedItems" => [],
        ];
        try {
            $soapClient = new SoapClient($this->initUrl);
            $response   = $client->MultiplexedSalePaymentRequest([
                "requestData" => $params,
            ]);

            if ($response->MultiplexedSalePaymentRequestResult->Status != '0') {
                return [
                    'success' => false,
                    'message' => $response->MultiplexedSalePaymentRequestResult->Message,
                ];
            }

            $token = $response->MultiplexedSalePaymentRequestResult->Token;

            $gatewayUrl = sprintf($this->gatewayUrl, $token);
            return [
                'success'     => true,
                'method'      => 'get',
                'gateway_url' => $gatewayUrl,
                'token'       => $token,
                'data'        => [],
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
        $params = [
            "LoginAccount" => $this->pin,
            "Token"        => $this->getToken(),
        ];
        try {
            $soapClient = new SoapClient($this->verifyUrl);
            $response   = $soapClient->ConfirmPayment([
                "requestData" => $params,
            ]);

            if ($result->ConfirmPaymentResult->Status != '0') {
                return [
                    'succsss' => false,
                    'message' => $result->ConfirmPaymentResult->Message,
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
        if (isset($requestData['status'])) {
            $status = $requestData['status'] == 0;
        }

        $this->data = [
            'status' => $status,

            'mid'   => $requestData['TerminalNo'] ?? NULL,
            'token' => $requestData['Token'] ?? NULL,


            'reserve_number'           => NULL,
            'reference_number'         => $requestData['RRN'] ?? NULL,
            'trace_number'             => NULL,
            'customer_refrence_number' => NULL,
            'transaction_amount'       => $requestData['Amount'] ?? NULL,

            'card_hashed' => NULL,
            'card_number' => NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
