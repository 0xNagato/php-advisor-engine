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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    ],

    'simple_texting' => [
        'api_key' => env('SIMPLE_TEXTING_API_KEY'),
        'base_uri' => env('SIMPLE_TEXTING_BASE_URI'),
        'from_phone' => env('SIMPLE_TEXTING_FROM_PHONE'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'from_whatsapp' => env('TWILIO_FROM_WHATSAPP'),
    ],

    'openexchangerates' => [
        'app_id' => env('OPEN_EXCHANGE_RATES_APP_ID'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'manychat' => [
        'api_key' => env('MANYCHAT_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'asana' => [
        'token' => env('ASANA_TOKEN'),
        'workspace_id' => env('ASANA_WORKSPACE_ID'),
        'project_id' => env('ASANA_PROJECT_ID'),
    ],

    'clicksend' => [
        'username' => env('CLICKSEND_USERNAME'),
        'api_key' => env('CLICKSEND_API_KEY'),
        'from' => env('CLICKSEND_FROM'),
        'from_numbers' => [
            'GB' => env('CLICKSEND_FROM_GB'),
        ],
    ],
];
