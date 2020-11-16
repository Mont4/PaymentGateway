<?php

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SoapClient;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;

class IrPec extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [];

    private $pin;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;

    public function __construct($config)
    {
        $this->pin        = $config['pin'];
        $this->gatewayUrl = "https://pec.shaparak.ir/NewIPG/?Token=%s";
        $this->initUrl    = "https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL";
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
            $response   = $soapClient->MultiplexedSalePaymentRequest([
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
                'data'        => [
                    'Token' => $token
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
        $params = [
            "LoginAccount" => $this->pin,
            "Token"        => $this->getToken(),
        ];
        try {
            $soapClient = new SoapClient($this->verifyUrl);
            $response   = $soapClient->ConfirmPayment([
                "requestData" => $params,
            ]);

            if ($response->ConfirmPaymentResult->Status != '0') {
                return [
                    'success' => false,
                    'message' => $response->ConfirmPaymentResult->Message,
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


            'reserve_number'            => NULL,
            'reference_number'          => $requestData['RRN'] ?? NULL,
            'trace_number'              => NULL,
            'customer_reference_number' => NULL,
            'transaction_amount'        => $requestData['Amount'] ?? NULL,

            'card_hashed' => NULL,
            'card_number' => NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
