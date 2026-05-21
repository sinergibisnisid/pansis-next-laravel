<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
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

    'mediamtx' => [
        'api_url' => env('MEDIAMTX_API_URL', 'http://localhost:9997'),
        'rtsp_url' => env('MEDIAMTX_RTSP_URL', 'rtsp://localhost:8554'),
        'webrtc_url' => env('MEDIAMTX_WEBRTC_URL', 'http://localhost:8889'),
    ],

    'whatsapp' => [
        'gateway_url' => env('WHATSAPP_GATEWAY_URL'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN'),
        'sender' => env('WHATSAPP_GATEWAY_SENDER'),
    ],
];
