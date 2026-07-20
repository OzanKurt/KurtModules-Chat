<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Models\MessageFlag;
use Kurt\Modules\Chat\Models\Participant;
use Kurt\Modules\Chat\Models\Presence;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Mentions\Models\Mention;

return [
    'broadcasting' => [
        'enabled' => true,
        'connection' => env('CHAT_BROADCAST_CONNECTION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP / REST API
    |--------------------------------------------------------------------------
    |
    | The out-of-the-box JSON REST surface, built on the Core API kit. It is a
    | complement to real-time broadcasting, not a replacement: sending a message
    | over HTTP still dispatches the domain event so websocket clients update.
    |
    | Safe-by-default: `mode` is `headless`, so no routes register until a
    | consumer opts in with CHAT_HTTP_MODE=api (or `ui`). Chat has no public
    | surface — every endpoint requires an authenticated, participating user, so
    | the auth middleware is applied to the whole group (not just writes).
    |
    */
    'http' => [
        'mode' => env('CHAT_HTTP_MODE', 'headless'),
        'prefix' => 'api/chat',
        'middleware' => ['api'],
        'auth_middleware' => ['auth'],
        'rate_limit' => '60,1',
    ],
    'edit_window_minutes' => 15,
    'message_max_length' => 4000,
    'auto_unarchive_on_new_message' => true,
    'encrypt_messages' => env('CHAT_ENCRYPT_MESSAGES', false),
    'attachments' => [
        'disk' => env('CHAT_MEDIA_DISK', 'public'),
        'max_size_kb' => 25_000,
        'allowed_mimes' => ['image/*', 'video/mp4', 'application/pdf'],
    ],
    'mentions' => [
        'pattern' => '/@([a-zA-Z0-9_.-]{2,40})/',
        'resolver' => null,
        'username_column' => 'username',
    ],
    'presence' => [
        'persist' => true,
        'heartbeat_seconds' => 30,
        'offline_after_seconds' => 90,
    ],
    'models' => [
        'conversation' => Conversation::class,
        'participant' => Participant::class,
        'message' => Message::class,
        'message_flag' => MessageFlag::class,
        'reaction' => Reaction::class,
        'mention' => Mention::class,
        'presence' => Presence::class,
    ],
];
