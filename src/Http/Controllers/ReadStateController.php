<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Chat\Http\Concerns\InteractsWithChatUser;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Core\Http\Controllers\ApiController;

final class ReadStateController extends ApiController
{
    use InteractsWithChatUser;

    /**
     * Mark the conversation read for the authenticated user. Reads up to the
     * given message (its created_at) when `message_id` is supplied, otherwise
     * up to now.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $data = $this->validate($request, [
            'message_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $readAt = now();
        if (! empty($data['message_id'])) {
            /** @var Message|null $message */
            $message = $conversation->messages()->whereKey($data['message_id'])->first();

            if ($message !== null) {
                $readAt = $message->created_at;
            }
        }

        $user = $this->chatUser($request);

        $conversation->participants()
            ->where('user_id', $user->getKey())
            ->update(['last_read_at' => $readAt]);

        return $this->respond([
            'conversation_id' => $conversation->id,
            'last_read_at' => $readAt->toIso8601String(),
            'unread_count' => $conversation->unreadCountFor($user),
        ]);
    }

    /**
     * Unread message count for the authenticated user in this conversation.
     */
    public function count(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->respond([
            'conversation_id' => $conversation->id,
            'unread_count' => $conversation->unreadCountFor($this->chatUser($request)),
        ]);
    }
}
