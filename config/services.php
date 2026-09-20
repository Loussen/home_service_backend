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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        // Speech-to-text: gpt-4o-transcribe >> whisper-1 for AZ/RU accuracy.
        'transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-transcribe'),
        // Optional ISO-639-1 hint (az|ru|en). Empty = auto-detect (better for mixed speech).
        'transcribe_language' => env('OPENAI_TRANSCRIBE_LANGUAGE'),
        // Intent repair / structured parse after ASR.
        'parse_model' => env('OPENAI_PARSE_MODEL', 'gpt-4o'),
    ],

];
