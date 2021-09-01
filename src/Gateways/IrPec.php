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
        $this->redirect   = $config['callback_url'];
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
            $response   = $soapClient->SalePaymentRequest([
                "requestData" => $params,
            ]);

            if ($response->SalePaymentRequestResult->Status != '0') {
                return [
                    'success' => false,
                    'message' => $response->SalePaymentRequestResult->Message,
                    'status'  => $response->SalePaymentRequestResult->Status,
                ];
            }

            $token = $response->SalePaymentRequestResult->Token;

            $gatewayUrl = sprintf($this->gatewayUrl, $token);
            return [
                'success'     => true,
                'method'      => 'get',
                'gateway_url' => $gatewayUrl,
                'token'       => $token,
                'data'        => [
                    'Token' => $token,
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

    public function setRequest(Request $request)
    {
        $requestData = $request->all();

        $status = false;
        if (isset($requestData['status'])) {
            $status = $requestData['status'] == 0;
        }

        $this->data = [
            'status' => $status,

            'mid'      => $requestData['TerminalNo'] ?? NULL,
            'token'    => $requestData['Token'] ?? NULL,
            'order_id' => $requestData['OrderId'] ?? NULL,


            'reserve_number'            => NULL,
            'reference_number'          => $requestData['RRN'] ?? NULL,
            'trace_number'              => $requestData['STraceNo'] ?? NULL,
            'customer_reference_number' => NULL,
            'transaction_amount'        => $requestData['Amount'] ?? NULL,

            'card_hashed' => NULL,
            'card_number' => $requestData['HashCardNumber'] ?? NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
