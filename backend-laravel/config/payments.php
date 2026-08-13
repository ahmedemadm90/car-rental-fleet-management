<?php

return [
    'default' => env('PAYMENT_PROVIDER', 'paymob'),

    'paymob' => [
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'currency' => env('PAYMOB_CURRENCY', 'EGP'),
        'checkout_template' => env('PAYMOB_CHECKOUT_TEMPLATE', 'https://accept.paymob.com/unifiedcheckout/?publicKey={public_key}&clientSecret={client_secret}'),
    ],
];
