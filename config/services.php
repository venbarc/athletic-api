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

    'atheletic' => [
        'base_url' => env('ATHELETIC_API_BASE_URL', 'https://rcm-api.athelas.com'),
        'username' => env('ATHELETIC_API_USERNAME'),
        'password' => env('ATHELETIC_API_PASSWORD'),
        'timeout' => (int) env('ATHELETIC_API_TIMEOUT', 120),
        'verify_ssl' => filter_var(env('ATHELETIC_API_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'appts_chunk_size' => (int) env('ATHELETIC_APPTS_CHUNK_SIZE', 200),
        'eligibility_checks_chunk_size' => (int) env('ATHELETIC_ELIGIBILITY_CHECKS_CHUNK_SIZE', 200),
        'prior_auths_chunk_size' => (int) env('ATHELETIC_PRIOR_AUTHS_CHUNK_SIZE', 200),
    ],

];