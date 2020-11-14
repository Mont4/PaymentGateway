<?php
/**
 * Created by PhpStorm.
 * User: iMohammad
 * Date: 6/20/17
 * Time: 8:42 PM
 */

namespace Mont4\PaymentGateway\Gateways\Contract;

use Illuminate\Http\Request;

interface GatewayInterface
{
    public function request();

    public function verify();

    public function setRequest(Request $request);
}
