<?php

return [
    'mode' => env('LARAPAY_MODE', 'sandbox'),
    'gateway' => env('LARAPAY_GATEWAY', 'paypal'),
    'paypal' => [
        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
            'app_id' => env('PAYPAL_LIVE_APP_ID', ''),
        ],
        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
            'app_id' => env('PAYPAL_SANDBOX_APP_ID', ''),
        ],
        // Sale, Authorization or Order
        'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'),
        'currency'       => env('PAYPAL_CURRENCY', 'USD'),
        // callback url
        'notify_url'     => env('PAYPAL_NOTIFY_URL', ''), 
        'locale'         => env('PAYPAL_LOCALE', 'en_US'),
        'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true),
    ],
    'paytabs' => [
        'profile_id' => env('PAYTABS_PROFILE_ID', ''),
        'live' => [
            'server_key' => env('PAYTABS_LIVE_SERVER_KEY', ''),
            'client_key' => env('PAYTABS_LIVE_CLIENT_KEY', ''),
        ],
        'sandbox' => [
            'server_key' => env('PAYTABS_SANDBOX_SERVER_KEY', ''),
            'client_key' => env('PAYTABS_SANDBOX_CLIENT_KEY', ''),
        ],
        'callback' => '',
        'return' => '',
        'endpoint' => env('PAYTABS_END_POINT', 'https://secure-egypt.paytabs.com/payment/request'), 
    ]
];
