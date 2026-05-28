<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Broadcast;
use Kurt\Modules\Chat\Models\Conversation;

Broadcast::channel('chat.room.{conversationId}', function ($user, int $conversationId) {
    return Conversation::query()
        ->where('id', $conversationId)
        ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user->getAuthIdentifier()))
        ->exists();
});

Broadcast::channel('chat.dm.{conversationId}', function ($user, int $conversationId) {
    return Conversation::query()
        ->where('id', $conversationId)
        ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user->getAuthIdentifier()))
        ->exists();
});

Broadcast::channel('chat.user.{userId}', function ($user, int $userId) {
    return (int) $user->getAuthIdentifier() === $userId;
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) {
    $isParticipant = Conversation::query()
        ->where('id', $conversationId)
        ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user->getAuthIdentifier()))
        ->exists();

    if (! $isParticipant) {
        return false;
    }

    return [
        'id' => $user->getAuthIdentifier(),
        'name' => $user->name ?? $user->email ?? null,
    ];
});
