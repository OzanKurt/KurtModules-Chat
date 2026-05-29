<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        $count = 0;

        /** @var Collection<int, Participant> $participants */
        $participants = $this->chatParticipants()->with('conversation')->get();

        foreach ($participants as $participant) {
            $query = $participant->conversation
                ->messages()
                ->where('user_id', '!=', $this->getKey());

            if ($participant->last_read_at !== null) {
                $query->where('created_at', '>', $participant->last_read_at);
            }

            $count += $query->count();
        }

        return $count;
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
