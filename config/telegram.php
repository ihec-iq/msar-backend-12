<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Telegram Bot integration including bot token,
    | webhook settings, and notification preferences.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'webhook' => [
        'base_url' => env('TELEGRAM_WEBHOOK_BASE_URL', env('APP_URL')),
        'path' => '/api/bot/onBoard',
    ],

    'notifications' => [
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
        'default_chat_ids' => env('TELEGRAM_DEFAULT_CHAT_IDS', ''),
    ],

    'api' => [
        'base_url' => 'https://api.telegram.org/bot',
        'timeout' => env('TELEGRAM_API_TIMEOUT', 30),
    ],

];
