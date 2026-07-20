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
