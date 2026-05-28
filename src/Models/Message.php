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
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Conversation $conversation
 * @property Message|null $parent
 * @property Collection<int, Message> $replies
 * @property Collection<int, Reaction> $reactions
 * @property Collection<int, Mention> $mentions
 */
class Message extends Model implements HasMedia
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'chat_messages';

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_id',
        'body',
        'edited_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'edited_at' => 'datetime',
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
     * @return HasMany<Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * @return HasMany<Mention, $this>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    public function reactWith(Model $user, string $emoji): Reaction
    {
        /** @var Reaction $reaction */
        $reaction = $this->reactions()->firstOrCreate([
            'user_id' => $user->getKey(),
            'emoji' => $emoji,
        ]);

        return $reaction;
    }

    public function unreactWith(Model $user, string $emoji): void
    {
        $this->reactions()
            ->where('user_id', $user->getKey())
            ->where('emoji', $emoji)
            ->delete();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('chat-attachments');
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
