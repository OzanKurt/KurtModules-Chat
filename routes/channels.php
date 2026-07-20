<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Kurt\Modules\Chat\Models\Conversation;

$authorizesConversation = function ($user, int $conversationId): bool {
    $conversation = Conversation::query()->find($conversationId);

    return $conversation !== null && $conversation->hasParticipant($user);
};

Broadcast::channel('chat.room.{conversationId}', $authorizesConversation);

Broadcast::channel('chat.dm.{conversationId}', $authorizesConversation);

Broadcast::channel('chat.user.{userId}', function ($user, int $userId) {
    return (int) $user->getAuthIdentifier() === $userId;
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) use ($authorizesConversation) {
    if (! $authorizesConversation($user, $conversationId)) {
        return false;
    }

    return [
        'id' => $user->getAuthIdentifier(),
        'name' => $user->name ?? $user->email ?? null,
    ];
});
