<?php

return [
    'gateways' => [
        'ir_pay' => [
            'gateway' => 'ir_pay',

            'api_key'     => env('PG_IR_PAY__API_KEY', 'test'),

            'request_url' => env('PG_IR_PAY__REQUEST_URL', 'https://pay.ir/pg/send'),
            'gateway_url' => env('PG_IR_PAY__GATEWAY_URL', 'https://pay.ir/pg'),
            'verify_url'  => env('PG_IR_PAY__VERIFY_URL', 'https://pay.ir/pg/verify'),

            'callback_url'    => env('APP_URL') . '/payment/verify?gateway=ir_pay',
        ],
        'ir_sep' => [
            'gateway' => 'ir_sep',

            'api_key'  => env('PG_IR_SEP__API_KEY', 'test'),
            'password' => env('PG_IR_SEP__VERIFY_URL'),

            'gateway_url' => env('PG_IR_SEP__GATEWAY_URL', 'https://sep.shaparak.ir/Payment.aspx'),
            'verify_url'  => env('PG_IR_SEP__VERIFY_URL', 'https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL'),

            'callback_url' => env('APP_URL') . '/payment/verify?gateway=ir_sep',
        ],
        'ir_bpm' => [
            'gateway' => 'ir_bpm',

            'terminal_id'   => env('PG_IR_BPM__TERMINAL_ID'),
            'user_name'     => env('PG_IR_BPM__USER_NAME'),
            'user_password' => env('PG_IR_BPM__USER_PASSWORD'),

            'request_url' => env('PG_IR_BPM__REQUEST_URL', 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'),
            'gateway_url' => env('PG_IR_BPM__GATEWAY_URL', 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat'),
            'verify_url'  => env('PG_IR_BPM__VERIFY_URL', 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'),
            'reverse_url' => env('PG_IR_BPM__REVERSE_URL', 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'),

            'callback_url_url' => env('APP_URL') . '/payment/verify?gateway=ir_bpm',
        ],
        'ir_top' => [
            'gateway' => 'ir_top',

            'api_key' => env('PG_IR_TOP__API_KEY', 'test'),

            'request_url' => env('PG_IR_TOP__REQUEST_URL', 'https://pay.ir/pg/send'),
            'gateway_url' => env('PG_IR_TOP__GATEWAY_URL', 'https://pay.ir/pg'),
            'verify_url'  => env('PG_IR_TOP__VERIFY_URL', 'https://pay.ir/pg/verify'),

            'callback_url' => env('URL_WEBSITE') . '/billing/verify',
        ],
    ],
];
