<?php


namespace Mont4\PaymentGateway\Gateways;


use Illuminate\Http\Request;

abstract class PaymentAbstract
{
    const HEADERS = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    protected $data = [];

    protected $amount      = NULL;
    protected $mobile      = NULL;
    protected $orderId     = NULL;
    protected $description = NULL;

    protected $request;

    public function setAmount($amount)
    {
        if ($amount < 1000)
            throw new \Exception('amount is lower than 1000');

        $this->amount = $amount;

        return $this;
    }

    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }


    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getResponse() :array
    {
        return $this->data;
    }

    public function getResponseBy($key) :?string
    {
        return $this->data[$key] ?? NULL;
    }

    public function getToken() :?string
    {
        return $this->getResponseBy('token');
    }


    protected function curlPost($url, $params, $headers = [])
    {
        $headers = array_merge(self::HEADERS, $headers);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}
