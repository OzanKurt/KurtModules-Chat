<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Message $message
 */
class MessageFlag extends Model
{
    use ResolvesUser;

    protected $table = 'chat_message_flags';

    /** @var list<string> */
    protected $fillable = ['message_id', 'user_id'];

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
}
