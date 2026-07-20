<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Chat\Http\Concerns\InteractsWithChatUser;
use Kurt\Modules\Chat\Http\Resources\ReactionResource;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Core\Http\Controllers\ApiController;

final class ReactionController extends ApiController
{
    use InteractsWithChatUser;

    /**
     * Add a reaction to a message. Idempotent: re-reacting with the same emoji
     * returns the existing reaction without re-broadcasting.
     */
    public function store(Request $request, Message $message): JsonResponse
    {
        $this->authorize('react', $message->conversation);

        $data = $this->validate($request, [
            'emoji' => ['required', 'string', 'max:64'],
        ]);

        $reaction = $message->reactWith($this->chatUser($request), $data['emoji']);

        return $this->respondCreated(ReactionResource::make($reaction));
    }

    /**
     * Remove the authenticated user's reaction (matching emoji) from a message.
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->authorize('react', $message->conversation);

        $data = $this->validate($request, [
            'emoji' => ['required', 'string', 'max:64'],
        ]);

        $message->unreactWith($this->chatUser($request), $data['emoji']);

        return $this->respondNoContent();
    }
}
