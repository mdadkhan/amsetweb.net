<?php

return [

    'default_currency' => env('PAYMENTS_DEFAULT_CURRENCY', 'usd'),

    // Local/staging only: replaces both gateways with an in-app mock checkout page.
    'fake' => env('PAYMENTS_FAKE_GATEWAYS', false),

    // Authoritative membership fees, keyed by membership plan slug. A slug listed
    // here overrides the price stored on the plan record.
    'membership_fees' => [
        'individual-annual' => env('MEMBERSHIP_FEE', 75),
        'student-annual' => env('MEMBERSHIP_FEE_STUDENT', 25),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'publishable' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

];
