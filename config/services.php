<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
        'callback_url' => env('FLUTTERWAVE_CALLBACK_URL'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
    ],

    'bachs' => [
        'api_key' => env('BACHS_API_KEY'),
        'webhook_secret' => env('BACHS_WEBHOOK_SECRET'),
        'callback_url' => env('BACHS_CALLBACK_URL'),
        'cancel_url' => env('BACHS_CANCEL_URL'),
        'base_url' => env('BACHS_BASE_URL', 'https://sandbox-api.bachs.io'),
        'webhook_tolerance_seconds' => (int) env('BACHS_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],

    'notifications' => [
        'new_order_emails' => env('NEW_ORDER_EMAILS', ''),
        'payment_failed_emails' => env('PAYMENT_FAILED_EMAILS', ''),
    ],

];
