<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:40 PM
 */

namespace Mont4\PaymentGateway\Gateways;

use Illuminate\Http\Request;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;
use Mont4\PaymentGateway\PaymentGateway;

class ComZarinpal extends PaymentAbstract implements GatewayInterface
{
    const VERIFY_STATUS = [];

    private $merchantId;
    private $requestUrl;
    private $gatewayUrl;
    private $verifyUrl;
    private $redirect;

    public function __construct($config)
    {
        $this->merchantId = $config['merchantId'];
        $this->requestUrl = $config['request_url'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->verifyUrl  = $config['verify_url'];
        $this->redirect   = $config['redirect'] . "?gateway=" . PaymentGateway::COM_ZARINPAL;
    }

    public function request()
    {
        try {
            $body = [
                'merchant_id' => $this->merchantId,

                'amount'      => $this->amount,
                'mobile'      => $this->mobile,
                'description' => $this->description,

                'callback_url' => $this->redirect,
            ];

            // request to pay.ir for token
            $response = $this->curlPost($this->requestUrl, $body);
            if (!$response)
                return [
                    'success' => false,
                ];

            $response = json_decode($response);
            if ($response->data->code == 100) {
                $gatewayUrl = sprintf($this->gatewayUrl, $response->data->authority);

                return [
                    'success'     => true,
                    'method'      => 'get',
                    'gateway_url' => $gatewayUrl,
                    'token'       => $response->data->authority,
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
            $response = $this->curlPost($this->verifyUrl, [
                'merchant_id' => $this->merchantId,
                'amount'      => $this->amount,
                'authority'   => $this->getToken(),
            ]);
            if (!$response)
                return [
                    'success' => false,
                ];

            \Log::info($response);

            $response = json_decode($response);
            if (!$response->Status) {
                return [
                    'success' => false,
                    'message' => self::VERIFY_STATUS[$response->Status],
                ];

            }

            if ($response->Status == 100) {
                $this->data['reference_number'] = $response->RefID;

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

        $this->data = [
            'status' => NULL,

            'mid'   => NULL,
            'token' => $requestData['Authority'] ?? NULL,


            'reserve_number'            => NULL,
            'reference_number'          => NULL,
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
