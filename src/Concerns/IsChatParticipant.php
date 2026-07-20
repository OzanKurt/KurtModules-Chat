<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Kurt\Modules\Chat\Enums\MessageType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Models\Participant;
use Kurt\Modules\Chat\Models\Presence;

/**
 * Adds chat-related relationships and convenience methods to a User model.
 *
 * @mixin Model
 */
trait IsChatParticipant
{
    /**
     * @return HasMany<Participant, $this>
     */
    public function chatParticipants(): HasMany
    {
        return $this->hasMany(Participant::class, 'user_id', $this->getKeyName());
    }

    /**
     * @return HasManyThrough<Conversation, Participant, $this>
     */
    public function chatConversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            Participant::class,
            'user_id',
            'id',
            $this->getKeyName(),
            'conversation_id',
        );
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id', $this->getKeyName());
    }

    /**
     * @return HasOne<Presence, $this>
     */
    public function chatPresence(): HasOne
    {
        return $this->hasOne(Presence::class, 'user_id', $this->getKeyName());
    }

    public function unreadChatMessagesCount(): int
    {
        $userId = $this->getKey();

        // Single grouped aggregate instead of one COUNT per conversation: join
        // the user's participant rows to their conversations' messages and apply
        // each row's own last_read_at cutoff via a column comparison. Excludes
        // the reader's own messages and author-less system messages.
        return (int) Message::query()
            ->join(
                'chat_participants',
                'chat_participants.conversation_id',
                '=',
                'chat_messages.conversation_id',
            )
            ->where('chat_participants.user_id', $userId)
            ->where('chat_messages.user_id', '!=', $userId)
            ->where('chat_messages.type', '!=', MessageType::System->value)
            ->where(function (Builder $q): void {
                $q->whereNull('chat_participants.last_read_at')
                    ->orWhereColumn(
                        'chat_messages.created_at',
                        '>',
                        'chat_participants.last_read_at',
                    );
            })
            ->count();
    }

    public function getChatDisplayName(): string
    {
        return $this->getAttribute('name') ?? $this->getAttribute('email') ?? (string) $this->getKey();
    }

    public function getChatAvatarUrl(): ?string
    {
        $value = $this->getAttribute('avatar_url') ?? $this->getAttribute('avatar');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
