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

final class ReactionRemoved implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $messageId,
        public readonly int $userId,
        public readonly string $emoji,
        public readonly int $conversationId,
        public readonly ConversationType $conversationType,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $isDirect = $this->conversationType === ConversationType::Direct;

        return [
            $isDirect
                ? new PrivateChannel("chat.dm.{$this->conversationId}")
                : new PrivateChannel("chat.room.{$this->conversationId}"),
        ];
    }
}
