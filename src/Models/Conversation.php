<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\ConversationFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Enums\MessageType;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Events\MessageSent;
use Kurt\Modules\Chat\Events\UserStartedTyping;
use Kurt\Modules\Chat\Events\UserStoppedTyping;
use Kurt\Modules\Chat\Support\ConversationKey;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property ConversationType $type
 * @property string|null $name
 * @property string|null $description
 * @property array<string, mixed>|null $data
 * @property string|null $dm_key
 * @property ConversationVisibility $visibility
 * @property int $created_by
 * @property Carbon|null $last_message_at
 * @property Collection<int, Participant> $participants
 * @property Collection<int, Message> $messages
 * @property Collection<int, Message> $rootMessages
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'chat_conversations';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'name',
        'description',
        'data',
        'dm_key',
        'visibility',
        'created_by',
        'last_message_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => ConversationType::class,
        'visibility' => ConversationVisibility::class,
        'last_message_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Whether the given user is a participant of this conversation. This is the
     * authorization primitive behind the private/presence broadcast channels.
     */
    public function hasParticipant(Authenticatable $user): bool
    {
        return $this->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function rootMessages(): HasMany
    {
        return $this->messages()->whereNull('parent_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->userBelongsTo('created_by');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeRooms(Builder $q): Builder
    {
        return $q->where('type', ConversationType::Room->value);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeDirect(Builder $q): Builder
    {
        return $q->where('type', ConversationType::Direct->value);
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $q, Model $user): Builder
    {
        return $q->where(function (Builder $inner) use ($user): void {
            $inner->whereIn('visibility', [
                ConversationVisibility::Public->value,
                ConversationVisibility::Unlisted->value,
            ])->orWhereHas('participants', fn (Builder $p) => $p->where('user_id', $user->getKey()));
        });
    }

    public static function directBetween(Model $a, Model $b): self
    {
        $key = ConversationKey::forDirect($a, $b);

        /** @var self|null $existing */
        $existing = static::query()->where('dm_key', $key)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            /** @var self $created */
            $created = DB::transaction(function () use ($a, $b, $key): self {
                /** @var self $convo */
                $convo = static::query()->create([
                    'type' => ConversationType::Direct,
                    'dm_key' => $key,
                    'created_by' => $a->getKey(),
                    'visibility' => ConversationVisibility::Private,
                ]);

                $now = now();
                $convo->participants()->createMany([
                    [
                        'user_id' => $a->getKey(),
                        'role' => ParticipantRole::Member->value,
                        'joined_at' => $now,
                        'notifications' => ParticipantNotifications::All->value,
                    ],
                    [
                        'user_id' => $b->getKey(),
                        'role' => ParticipantRole::Member->value,
                        'joined_at' => $now,
                        'notifications' => ParticipantNotifications::All->value,
                    ],
                ]);

                return $convo;
            });

            return $created;
        } catch (QueryException $e) {
            // A concurrent caller won the race and inserted the same dm_key
            // first; the unique index on dm_key rejected our insert. Return the
            // conversation that actually persisted instead of surfacing the
            // duplicate-key violation.
            /** @var self|null $winner */
            $winner = static::query()->where('dm_key', $key)->first();

            if ($winner !== null) {
                return $winner;
            }

            throw $e;
        }
    }

    public function send(Model $author, string $body, ?Message $parent = null): Message
    {
        /** @var Message $message */
        $message = $this->messages()->create([
            'user_id' => $author->getKey(),
            'parent_id' => $parent?->getKey(),
            'body' => $body,
        ]);

        $this->forceFill(['last_message_at' => now()])->save();

        if ((bool) config('chat.auto_unarchive_on_new_message', true)) {
            $this->participants()
                ->where('user_id', '!=', $author->getKey())
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null]);
        }

        /** @var Message $fresh */
        $fresh = $message->fresh(['mentions']) ?? $message;
        MessageSent::dispatch($fresh);

        return $message;
    }

    /**
     * Create an automated, author-less system message ("X joined", "topic changed", …).
     *
     * @param  array<string, mixed>  $data
     */
    public function systemMessage(string $body, array $data = []): Message
    {
        /** @var Message $message */
        $message = $this->messages()->create([
            'user_id' => null,
            'type' => MessageType::System,
            'body' => $body,
            'data' => $data,
        ]);

        $this->forceFill(['last_message_at' => now()])->save();

        /** @var Message $fresh */
        $fresh = $message->fresh() ?? $message;
        MessageSent::dispatch($fresh);

        return $message;
    }

    /**
     * @return CursorPaginator<int, Message>
     */
    public function messagesCursor(int $perPage = 50): CursorPaginator
    {
        /** @var CursorPaginator<int, Message> $paginator */
        $paginator = $this->messages()
            ->latest('created_at')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);

        return $paginator;
    }

    /**
     * Broadcast that the given user has started typing in this conversation.
     */
    public function startTyping(Model $user): void
    {
        UserStartedTyping::dispatch($user, (int) $this->getKey());
    }

    /**
     * Broadcast that the given user has stopped typing in this conversation.
     */
    public function stopTyping(Model $user): void
    {
        UserStoppedTyping::dispatch($user, (int) $this->getKey());
    }

    public function markRead(Model $user): void
    {
        $this->participants()
            ->where('user_id', $user->getKey())
            ->update(['last_read_at' => now()]);
    }

    public function unreadCountFor(Model $user): int
    {
        /** @var Participant|null $participant */
        $participant = $this->participants()
            ->where('user_id', $user->getKey())
            ->first();

        if ($participant === null) {
            return 0;
        }

        $since = $participant->last_read_at;

        // Mirror IsChatParticipant::unreadChatMessagesCount: a message is only
        // "unread" for someone else's non-system content, so exclude the
        // reader's own messages and author-less system messages.
        $query = $this->messages()
            ->where('user_id', '!=', $user->getKey())
            ->where('type', '!=', MessageType::System->value);

        if ($since !== null) {
            $query->where('created_at', '>', $since);
        }

        return $query->count();
    }

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }
}
