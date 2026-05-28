<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\MentionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property Carbon|null $seen_at
 * @property Message $message
 */
class Mention extends Model
{
    /** @use HasFactory<MentionFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'chat_mentions';

    /** @var list<string> */
    protected $fillable = [
        'message_id',
        'user_id',
        'seen_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'seen_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): MentionFactory
    {
        return MentionFactory::new();
    }
}
