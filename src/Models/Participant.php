<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property ParticipantRole $role
 * @property Carbon $joined_at
 * @property Carbon|null $last_read_at
 * @property Carbon|null $muted_until
 * @property ParticipantNotifications $notifications
 * @property Conversation $conversation
 */
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'chat_participants';

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'muted_until',
        'notifications',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'role' => ParticipantRole::class,
        'notifications' => ParticipantNotifications::class,
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'muted_until' => 'datetime',
    ];

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

    protected static function newFactory(): ParticipantFactory
    {
        return ParticipantFactory::new();
    }
}
