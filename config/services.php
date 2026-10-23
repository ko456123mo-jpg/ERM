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
        'url' => env('FASTAPI_ENGINE_URL', env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8001')),
        'jwt_private_key_path' => env('ERM_JWT_PRIVATE_KEY_PATH', ''),
        'jwt_issuer' => env('ERM_JWT_ISSUER', 'erm-laravel'),
        'jwt_audience' => env('ERM_JWT_AUDIENCE', 'erm-tool-gateway'),
        'timeout' => (int) env('FASTAPI_TIMEOUT', 300),
    ],

    'frida' => [
        'url' => env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('FRIDA_TIMEOUT', 30),
    ],

    'mobsf' => [
        'url' => env('MOBSF_SERVICE_URL', 'http://127.0.0.1:8002'),
        'api_key' => env('MOBSF_API_KEY', ''),
        'timeout' => (int) env('MOBSF_TIMEOUT', 120),
    ],

    'objection' => [
        'url' => env('OBJECTION_SERVICE_URL', 'http://127.0.0.1:8003'),
        'timeout' => (int) env('OBJECTION_TIMEOUT', 60),
    ],

    'bootstrap_admin' => [
        'username' => env('DEFAULT_ADMIN_USERNAME', 'admin'),
        'email' => env('DEFAULT_ADMIN_EMAIL', 'admin@example.invalid'),
        'password' => env('DEFAULT_ADMIN_PASSWORD', ''),
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
