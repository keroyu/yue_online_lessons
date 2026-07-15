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

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
    ],

    'portaly' => [
        'webhook_key' => env('PORTALY_WEBHOOK_KEY'),
    ],

    'payuni' => [
        'merchant_id' => env('PAYUNI_MERCHANT_ID'),
        'hash_key'    => env('PAYUNI_HASH_KEY'),
        'hash_iv'     => env('PAYUNI_HASH_IV'),
        'sandbox'     => env('PAYUNI_SANDBOX', false),
    ],

    'cloudflare_stream' => [
        // Subdomain code from the Stream dashboard embed snippet: customer-{code}.cloudflarestream.com
        'customer_code' => env('CLOUDFLARE_STREAM_CUSTOMER_CODE'),
        // Signing key created once via POST /accounts/{account_id}/stream/keys;
        // private_key is the base64-encoded PEM exactly as returned by that API.
        'key_id'        => env('CLOUDFLARE_STREAM_KEY_ID'),
        'private_key'   => env('CLOUDFLARE_STREAM_PRIVATE_KEY'),
        'token_ttl'     => env('CLOUDFLARE_STREAM_TOKEN_TTL', 43200),
    ],

    'newebpay' => [
        'merchant_id' => env('NEWEBPAY_MERCHANT_ID'),
        'hash_key'    => env('NEWEBPAY_HASH_KEY'),
        'hash_iv'     => env('NEWEBPAY_HASH_IV'),
        'env'         => env('NEWEBPAY_ENV', 'sandbox'),
    ],

];
