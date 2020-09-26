<?php


namespace Mont4\PaymentGateway\Gateways;


use Illuminate\Http\Request;

abstract class PaymentAbstract
{
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

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }


    protected function curl_post($url, $params)
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
