<?php

return [
    // Mode: live or sandbox
    'mode' => env('LARAPAY_MODE', 'sandbox'),
    // default Gateway from support Gateways list (paypal, paytabs, paymob)
    'gateway' => env('LARAPAY_GATEWAY', 'paypal'),
    // default currency
    'currency' => env('LARAPAY_CURRENCY', 'EGP'),

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
        // named callback in paytabs docs
        'server_callback' => null,
        // named return in paytabs docs
        'client_callback' => null,
        'endpoint' => env('PAYTABS_END_POINT', 'https://secure-egypt.paytabs.com/'), 
    ],
    'paymob' => [
        'live' => [
            'api_key' => env('PAYMOB_LIVE_API_KEY', ''),
            'secret_key' => env('PAYMOB_LIVE_SECRET_KEY', ''),
            'public_key' => env('PAYMOB_LIVE_PUBLIC_KEY', ''),
        ],
        'sandbox' => [
            'api_key' => env('PAYMOB_SANDBOX_API_KEY', ''),
            'secret_key' => env('PAYMOB_SANDBOX_SECRET_KEY', ''),
            'public_key' => env('PAYMOB_SANDBOX_PUBLIC_KEY', ''),
        ],
        'server_callback' => null,
        'client_callback' => null,
        // endpoint will created from secret key
    ],
    // Accepted Gateways
    'gateways' => ['paypal', 'paytabs', 'paymob'],
];
