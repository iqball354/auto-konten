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

    'meta' => [
    'app_id'       => env('META_APP_ID'),
    'app_secret'   => env('META_APP_SECRET'),
    'redirect_uri' => env('META_REDIRECT_URI'),
    'verify_ssl'   => env('META_HTTP_VERIFY_SSL', true),
    ],

    'groq' => [
    'api_key'  => env('GROQ_API_KEY'),
    'model'    => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],
    
];
