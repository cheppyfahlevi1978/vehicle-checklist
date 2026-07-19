<?php

return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
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
    'wa_gateway' => [
        'url' => env('WA_GATEWAY_URL'),
        'key' => env('WA_GATEWAY_KEY'),
        'webhook_secret' => env('WA_GATEWAY_WEBHOOK_SECRET'),
        'timeout' => env('WA_GATEWAY_TIMEOUT', 45),
    ],
];
