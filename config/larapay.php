<?php

return [
    // Mode: live or sandbox
    'mode' => env('LARAPAY_MODE', 'sandbox'),
    // default Gateway from support Gateways list (paypal, paytabs, paymob)
    'gateway' => env('LARAPAY_GATEWAY', 'paypal'),
    // default currency
    'currency' => env('LARAPAY_CURRENCY', 'EGP'),

    // Debug Mode: Log API requests/responses
    'debug' => env('LARAPAY_DEBUG', false),

    // Package route settings
    'routes' => [
        'prefix' => env('LARAPAY_ROUTE_PREFIX', 'larapay'),
        'middleware' => ['web'],
    ],

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
        'currency' => 'EGP',
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
    'kashier' => [
        'mid' => env('KASHIER_MID', ''),
        'live' => [
            'api_key'    => env('KASHIER_LIVE_API_KEY', ''),
            'secret_key' => env('KASHIER_LIVE_SECRET_KEY', ''),
        ],
        'sandbox' => [
            'api_key'    => env('KASHIER_SANDBOX_API_KEY', ''),
            'secret_key' => env('KASHIER_SANDBOX_SECRET_KEY', ''),
        ],
        // Redirect URL Kashier sends the customer back to after payment.
        // Leave null to use the auto-generated larapay.client-callback route.
        'client_callback' => null,
        // Webhook URL Kashier POSTs server-side notifications to.
        // Leave null to use the auto-generated larapay.server-callback route.
        'server_callback' => null,
        // Comma-separated list of allowed payment methods.
        // Options: card, wallet, bank_installments
        'allowed_methods' => env('KASHIER_ALLOWED_METHODS', 'card,wallet,bank_installments'),
        // Language for the hosted payment page ('en' or 'ar')
        'display' => env('KASHIER_DISPLAY', 'en'),
    ],

    // Accepted Gateways
    'gateways' => ['paypal', 'paytabs', 'paymob', 'kashier', 'payfort', 'tab'],

    'payfort' => [
        'live' => [
            'access_code'         => env('PAYFORT_LIVE_ACCESS_CODE', ''),
            'merchant_identifier' => env('PAYFORT_LIVE_MERCHANT_ID', ''),
            'sha_request_phrase'  => env('PAYFORT_LIVE_SHA_REQUEST_PHRASE', ''),
            'sha_response_phrase' => env('PAYFORT_LIVE_SHA_RESPONSE_PHRASE', ''),
        ],
        'sandbox' => [
            'access_code'         => env('PAYFORT_SANDBOX_ACCESS_CODE', ''),
            'merchant_identifier' => env('PAYFORT_SANDBOX_MERCHANT_ID', ''),
            'sha_request_phrase'  => env('PAYFORT_SANDBOX_SHA_REQUEST_PHRASE', ''),
            'sha_response_phrase' => env('PAYFORT_SANDBOX_SHA_RESPONSE_PHRASE', ''),
        ],
        // sha256 (recommended) | sha512 | sha1
        'sha_type' => env('PAYFORT_SHA_TYPE', 'sha256'),
        // en or ar
        'language' => env('PAYFORT_LANGUAGE', 'en'),
        // PURCHASE or AUTHORIZATION
        'command'  => env('PAYFORT_COMMAND', 'PURCHASE'),
        // Override the redirect-back URL (null = auto-generated larapay.client-callback)
        'return_url' => null,
    ],
    'tab' => [
        'mode' => env('TAB_MODE', 'live'),
        'merchant_id' => env('TAB_MERCHANT_ID', ''),
        'live' => [
            'api_key'    => env('TAB_LIVE_API_KEY', ''),
            'public_key' => env('TAB_LIVE_PUBLIC_KEY', ''),
        ],
        'sandbox' => [
            'api_key'    => env('TAB_SANDBOX_API_KEY', ''),
            'public_key' => env('TAB_SANDBOX_PUBLIC_KEY', ''),
        ],
        'custom_checkout_url' => env('TAB_CUSTOM_CHECKOUT_URL', ''),
        'server_callback' => null,
        'client_callback' => null,
    ],
];
