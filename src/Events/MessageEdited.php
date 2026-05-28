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
use Kurt\Modules\Chat\Models\Message;

final class MessageEdited implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Message $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        $isDirect = $conversation->type === ConversationType::Direct;

        return [
            $isDirect
                ? new PrivateChannel("chat.dm.{$conversation->id}")
                : new PrivateChannel("chat.room.{$conversation->id}"),
        ];
    }
}
