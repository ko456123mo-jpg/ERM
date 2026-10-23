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

    /*
    |--------------------------------------------------------------------------
    | ERM Security Platform Microservices
    |--------------------------------------------------------------------------
    */

    'fastapi' => [
        'url' => env('FASTAPI_ENGINE_URL', env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8000/api')),
        'secret' => env('FASTAPI_SHARED_SECRET', 'erm_enterprise_secret_key_change_in_production_2026'),
    ],

    'frida' => [
        'url' => env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('FRIDA_TIMEOUT', 30),
    ],

    'mobsf' => [
        'url' => env('MOBSF_SERVICE_URL', 'http://127.0.0.1:8002'),
        'api_key' => env('MOBSF_API_KEY', 'erm_mobsf_api_key_secure_2026'),
        'timeout' => (int) env('MOBSF_TIMEOUT', 120),
    ],

    'objection' => [
        'url' => env('OBJECTION_SERVICE_URL', 'http://127.0.0.1:8003'),
        'timeout' => (int) env('OBJECTION_TIMEOUT', 60),
    ],

    'jadx' => [
        'binary' => env('JADX_BINARY', '/usr/local/bin/jadx'),
        'timeout' => (int) env('JADX_TIMEOUT', 900),
    ],

    'ai' => [
        'url' => env('AI_SERVICE_URL', null),
        'key' => env('AI_API_KEY', null),
        'provider' => env('AI_PROVIDER', 'internal'),
    ],


];
