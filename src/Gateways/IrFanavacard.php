<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:40 PM
 */

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mont4\PaymentGateway\PaymentGateway;

class IrFanavacard extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [
        -1 => "ارسال api الزامی می باشد,",
        -2 => "ارسال transId الزامی می باشد,",
        -3 => "درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد,",
        -4 => "فروشنده غیر فعال می باشد,",
        -5 => "تراکنش با خطا مواجه شده است,",
    ];

    private $userId;
    private $password;

    private $requestUrl;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;

    public function __construct($config)
    {
        $this->userId   = $config['user_id'];
        $this->password = $config['password'];

        $this->requestUrl = "https://fcp.shaparak.ir/ref-payment/RestServices/mts/generateTokenWithNoSign/";
        $this->gatewayUrl = "https://fcp.shaparak.ir/_ipgw_//payment/?token=%s&lang=fa";
        $this->verifyUrl  = "https://fcp.shaparak.ir/ref-payment/RestServices/mts/verifyMerchantTrans/";
        $this->redirect   = $config['redirect'] . "?gateway=" . PaymentGateway::IR_FANAVACARD;
    }

    public function request()
    {
        if (!$this->orderId)
            $this->orderId = "ir_fanavacard_" . microtime(true) . '_' . Str::random(24);

        try {
            $body = [
                'WSContext' => [
                    'UserId'   => $this->userId,
                    'Password' => $this->password,
                ],

                'TransType' => "EN_GOODS",

                'Amount'     => $this->amount,
                'MobileNo'   => $this->mobile,
                'ReserveNum' => $this->orderId,

                'RedirectUrl' => $this->redirect,
            ];

            \Log::info($body);
            // request to pay.ir for token
            $response = $this->curlPost($this->requestUrl, $body);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->Result == 'erSucceed') {
                $gatewayUrl = sprintf($this->gatewayUrl, $response->Token);

                return [
                    'success'     => true,
                    'method'      => 'get',
                    'gateway_url' => $gatewayUrl,
                    'token'       => $response->Token,
                    'data'        => [],
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
        try {
            $body = [
                'WSContext' => [
                    'UserId'   => $this->userId,
                    'Password' => $this->password,
                ],

                'Token'  => $this->getToken(),
                'RefNum' => $this->getResponseBy('RefNum'),
            ];

            $response = $this->curlPost($this->verifyUrl, $body);
            \Log::info($response);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if (!$response->Result) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response->errorCode],
                ];
            }

            if ($response->Result == 'erSucceed') {
                return [
                    'success' => true,
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
            'token' => $requestData['token'] ?? NULL,


            'reserve_number'            => $requestData['ResNum'] ?? NULL,
            'reference_number'          => $requestData['RefNum'] ?? NULL,
            'trace_number'              => $requestData['TraceNo'] ?? NULL,
            'customer_reference_number' => $requestData['CustomerRefNum'] ?? NULL,
            'transaction_amount'        => $requestData['transactionAmount'] ?? NULL,

            'card_hashed' => $requestData['CardHashPan'] ?? NULL,
            'card_number' => $requestData['CardMaskPan'] ?? NULL,

            'mobile_number' => $requestData['mobileNo'] ?? NULL,
        ];

        return $this;
    }
}
