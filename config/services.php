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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'max' => [
        'token' => env('MAX_BOT_TOKEN'),
        'api_url' => env('MAX_API_URL', 'https://platform-api2.max.ru'),
        'admin_id' => env('MAX_ADMIN_ID'),
        'webhook_url' => env('MAX_WEBHOOK_URL'),
        'bot_link' => env('MAX_BOT_LINK', ''),
        // На shared-хостингах без актуального CA-бандла (cURL error 60) ставить false.
        'verify_ssl' => filter_var(env('MAX_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'gazelka' => [
        'token' => env('GAZELKA_TOKEN'),
        'base_url' => env('GAZELKA_BASE_URL', 'https://gazelka.space/api'),
        'timeout' => (int) env('GAZELKA_TIMEOUT', 30),
        // На shared-хостингах без актуального CA-бандла (cURL error 60) выключать false.
        'verify_ssl' => filter_var(env('GAZELKA_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // Доска рейтинга сотрудников (телевизор в цехе). Доступ по токену из URL.
    'rating_board' => [
        'token' => env('RATING_BOARD_ACCESS_TOKEN'),
    ],

];
