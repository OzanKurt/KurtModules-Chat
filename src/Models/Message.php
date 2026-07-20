<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\MessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Kurt\Modules\Chat\Enums\MessageType;
use Kurt\Modules\Chat\Events\ReactionAdded;
use Kurt\Modules\Chat\Events\ReactionRemoved;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Interactions\Engagement\Concerns\Reactable;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Mentions\Concerns\Mentionable;
use Kurt\Modules\Interactions\Mentions\Models\Mention;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Throwable;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property MessageType $type
 * @property string $body
 * @property array<string, mixed>|null $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $edited_at
 * @property Conversation $conversation
 * @property Message|null $parent
 * @property Collection<int, Message> $replies
 * @property Collection<int, Reaction> $reactions
 * @property Collection<int, Mention> $mentions
 * @property Collection<int, MessageFlag> $flags
 */
class Message extends Model implements HasMedia
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use Mentionable;
    use Reactable;
    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'chat_messages';

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_id',
        'type',
        'body',
        'data',
        'edited_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'edited_at' => 'datetime',
        'type' => MessageType::class,
        'data' => 'array',
    ];

    /**
     * Transient list of user keys to be persisted as mentions once the message
     * has been created. Populated by the MessageObserver during `saving`.
     *
     * @var array<int, int|string>
     */
    public array $pendingMentionUserIds = [];

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MessageFlag, $this>
     */
    public function flags(): HasMany
    {
        return $this->hasMany(MessageFlag::class);
    }

    public function isSystem(): bool
    {
        return $this->type === MessageType::System;
    }

    public function flag(Model $user): MessageFlag
    {
        /** @var MessageFlag $flag */
        $flag = $this->flags()->firstOrCreate([
            'user_id' => $user->getKey(),
        ]);

        return $flag;
    }

    public function unflag(Model $user): void
    {
        $this->flags()
            ->where('user_id', $user->getKey())
            ->delete();
    }

    public function isFlaggedBy(Model $user): bool
    {
        return $this->flags()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function reactWith(Model $user, string $emoji): Reaction
    {
        /** @var Reaction $reaction */
        $reaction = $this->reactions()->firstOrCreate([
            'user_id' => $user->getKey(),
            'emoji' => $emoji,
        ]);

        // Only broadcast when a reaction was actually added, keeping the
        // operation idempotent for repeat reactions with the same emoji.
        if ($reaction->wasRecentlyCreated) {
            ReactionAdded::dispatch($reaction);
        }

        return $reaction;
    }

    public function unreactWith(Model $user, string $emoji): void
    {
        $deleted = $this->reactions()
            ->where('user_id', $user->getKey())
            ->where('emoji', $emoji)
            ->delete();

        // Only broadcast when a reaction was actually removed.
        if ($deleted > 0) {
            ReactionRemoved::dispatch(
                (int) $this->getKey(),
                (int) $user->getKey(),
                $emoji,
            );
        }
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('chat-attachments');
    }

    public function getBodyAttribute(?string $value): ?string
    {
        if ($value === null || ! (bool) config('chat.encrypt_messages', false)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            // Legacy plaintext rows written before encryption was enabled.
            return $value;
        }
    }

    public function setBodyAttribute(?string $value): void
    {
        if ($value !== null && (bool) config('chat.encrypt_messages', false)) {
            $this->attributes['body'] = Crypt::encryptString($value);

            return;
        }

        $this->attributes['body'] = $value;
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeInThreadOf(Builder $q, self $root): Builder
    {
        return $q->where('parent_id', $root->getKey());
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }
}
