<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Models;

use Database\Factories\Kurt\Modules\Chat\PresenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Chat\Enums\PresenceStatus;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $user_id
 * @property PresenceStatus $status
 * @property string|null $status_message
 * @property Carbon $heartbeat_at
 */
class Presence extends Model
{
    /** @use HasFactory<PresenceFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'chat_presence';

    protected $primaryKey = 'user_id';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'status',
        'status_message',
        'heartbeat_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => PresenceStatus::class,
        'heartbeat_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): PresenceFactory
    {
        return PresenceFactory::new();
    }
}
