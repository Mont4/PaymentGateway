<?php


namespace Mont4\PaymentGateway\Gateways;


use Illuminate\Http\Request;
use Mont4\PaymentGateway\Gateways\Contract\GatewayInterface;
use Mont4\PaymentGateway\PaymentGateway;

class IrBpm extends PaymentAbstract implements GatewayInterface
{
    private const ERROR_CODE_MESSAGE = [
        11  => "شماره كارت نامعتبر است",
        12  => "موجودي كافي نيست",
        13  => "رمز نادرست است",
        14  => "تعداد دفعات وارد كردن رمز بيش از حد مجاز است",
        15  => "كارت نامعتبر است",
        16  => "دفعات برداشت وجه بيش از حد مجاز است",
        17  => "كاربر از انجام تراكنش منصرف شده است",
        18  => "تاريخ انقضاي كارت گذشته است",
        19  => "مبلغ برداشت وجه بيش از حد مجاز است",
        111 => "صادر كننده كارت نامعتبر است",
        112 => "خطاي سوييچ صادر كننده كارت",
        113 => "پاسخي از صادر كننده كارت دريافت نشد",
        114 => "دارنده كارت مجاز به انجام اين تراكنش نيست",
        21  => "پذيرنده نامعتبر است",
        23  => "خطاي امنيتي رخ داده است",
        24  => "اطلاعات كاربري پذيرنده نامعتبر است",
        25  => "مبلغ نامعتبر است",
        31  => "پاسخ نامعتبر است",
        32  => "فرمت اطلاعات وارد شده صحيح نمي باشد",
        33  => "حساب نامعتبر است",
        34  => "خطاي سيستمي",
        35  => "تاريخ نامعتبر است",
        41  => "شماره درخواست تكراري است",
        42  => "تراكنش Sale يافت نشد",
        43  => "قبلا درخواست Verify داده شده است",
        44  => "درخواست Verfiy يافت نشد",
        45  => "تراكنش Settle شده است",
        46  => "تراكنش Settle نشده است",
        47  => "تراكنش Settle يافت نشد",
        48  => "تراكنش Reverse شده است",
        49  => "تراكنش Refund يافت نشد",
        412 => "شناسه قبض نادرست است",
        413 => "شناسه پرداخت نادرست است",
        414 => "سازمان صادر كننده قبض نامعتبر است",
        415 => "زمان جلسه كاري به پايان رسيده است",
        416 => "خطا در ثبت اطلاعات",
        417 => "شناسه پرداخت كننده نامعتبر است",
        418 => "اشكال در تعريف اطلاعات مشتري",
        419 => "تعداد دفعات ورود اطلاعات از حد مجاز گذشته است",
        421 => "IP نامعتبر است",
        51  => "تراكنش تكراري است",
        54  => "تراكنش مرجع موجود نيست",
        55  => "تراكنش نامعتبر است",
        61  => "خطا در واريز",
    ];

    private $terminalId;
    private $userName;
    private $userPassword;

    private $requestUrl;
    private $gatewayUrl;
    private $verifyUrl;

    private $reverseUrl;
    private $callbackUrl;

    public function __construct($config)
    {
        $this->terminalId   = $config['terminal_id'];
        $this->userName     = $config['user_name'];
        $this->userPassword = $config['user_password'];

        $this->requestUrl = $config['request_url'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->verifyUrl  = $config['verify_url'];
        $this->reverseUrl = $config['reverse_url'];

        $this->callbackUrl = $config['callback_url'];
    }

    public function request()
    {
        $parameters = [
            'terminalId'   => $this->terminalId,
            'userName'     => $this->userName,
            'userPassword' => $this->userPassword,

            'orderId' => $this->orderId,
            'amount'  => $this->amount,

            'localDate' => date('Ymd'),
            'localTime' => date('Gis'),

            'additionalData' => $this->description,
            'callBackUrl'    => $this->callbackUrl,
        ];

        $namespace = 'http://interfaces.core.sw.bps.com/';

        $soapClient = new \SoapClient($this->requestUrl);
        $response   = $soapClient->bpPayRequest($parameters);

        if (is_numeric($response->return)) {
            return [
                'success' => false,
                'message' => self::ERROR_CODE_MESSAGE[$response->return],
                'code'    => $response->return,
            ];
        }

        $response     = explode(',', $response->return);
        $responseCode = $response[0];

        if ($responseCode == "0") {
            return [
                'success'     => true,
                'method'      => 'get',
                'gateway_url' => $this->gatewayUrl,
                'token'       => $response[1],
                'data'        => [
                    'Amount'      => $this->amount,
                    'RefId'       => $response[1],
                    'RedirectURL' => $this->gatewayUrl,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => '',
            'code'    => 0,
        ];
    }

    public function verify()
    {
        if (!$this->getResponseBy('status')) {
            return [
                'success' => false,
                'message' => self::ERROR_CODE_MESSAGE[$this->getResponseBy('status_code')],
                'code'    => $this->getResponseBy('status_code'),
            ];
        }

        $parameters = [
            'terminalId'   => $this->terminalId,
            'userName'     => $this->userName,
            'userPassword' => $this->userPassword,

            'orderId' => $this->orderId,

            'saleOrderId'     => $this->getResponseBy('order_id'),
            'saleReferenceId' => $this->getResponseBy('reference_number'),
        ];

        $soapClient = new \SoapClient($this->verifyUrl);
        $response   = $soapClient->bpVerifyRequest($parameters);

        if ($response->return == '0') {
            $response = $soapClient->bpSettleRequest($parameters);
            if ($response->return == '0') {
                return [
                    'success' => true,
                ];
            } else {
                $soapClient->bpReversalRequest($parameters);

                return [
                    'success' => false,
                    'message' => self::ERROR_CODE_MESSAGE[$response->return],
                    'code'    => $response->return,

                ];
            }
        }

        if (is_numeric($response->return)) {
            $soapClient->bpReversalRequest($parameters);

            return [
                'success' => false,
                'message' => self::ERROR_CODE_MESSAGE[$response->return],
                'code'    => $response->return,
            ];
        }
    }

    public function setRequest(Request $request)
    {
        $requestData = $request->all();

        app('log')->info($requestData);

        $status = false;
        if (isset($requestData['ResCode'])) {
            $status = $requestData['ResCode'] == 0;
        }

        $this->data = [
            'status'      => $status,
            'status_code' => $requestData['ResCode'],

            'mid'    => NULL,
            'token'  => NULL,
            'amount' => $requestData['FinalAmount'],


            'order_id' => $requestData['SaleOrderId'] ?? NULL,

            'reserve_number'            => $requestData['RefId'] ?? NULL,
            'reference_number'          => $requestData['SaleReferenceId'] ?? NULL,
            'trace_number'              => NULL,
            'customer_reference_number' => NULL,
            'transaction_amount'        => NULL,

            'card_hashed' => $requestData['CardHolderInfo'] ?? NULL,
            'card_number' => $requestData['CardHolderPan'] ?? NULL,

            'mobile_number' => NULL,
        ];

        return $this;
    }
}
