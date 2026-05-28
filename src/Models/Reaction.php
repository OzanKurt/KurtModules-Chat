<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\ReactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property string $emoji
 * @property Message $message
 */
class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'chat_reactions';

    /** @var list<string> */
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
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

    protected static function newFactory(): ReactionFactory
    {
        return ReactionFactory::new();
    }
}
