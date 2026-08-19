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

    'binance' => [
        'api_url' => env('BINANCE_API_URL', 'https://data-api.binance.vision'),
        'ws_url' => env('BINANCE_WS_URL', 'wss://data-stream.binance.vision:443'),
        'cache_ttl' => env('BINANCE_CACHE_TTL', 30), // seconds
        'timeout' => env('BINANCE_TIMEOUT', 5), // seconds
    ],

    'indodax' => [
        'base_url' => env('INDODAX_API_URL', 'https://indodax.com'),
        'timeout' => env('INDODAX_TIMEOUT', 10), // seconds
    ],

    'coingecko' => [
        'base_url' => env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3'),
        'key' => env('COINGECKO_API_KEY'),
    ],

];
