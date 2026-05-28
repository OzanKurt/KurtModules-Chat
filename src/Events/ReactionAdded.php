<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Reaction;

final class ReactionAdded implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Reaction $reaction) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->reaction->message->conversation;
        $isDirect = $conversation->type === ConversationType::Direct;

        return [
            $isDirect
                ? new PrivateChannel("chat.dm.{$conversation->id}")
                : new PrivateChannel("chat.room.{$conversation->id}"),
        ];
    }
}
