<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Chat\Http\Concerns\InteractsWithChatUser;
use Kurt\Modules\Chat\Http\Resources\MessageResource;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;

final class MessageController extends ApiController
{
    use HandlesApiQuery;
    use InteractsWithChatUser;

    /**
     * Paginated message history for a conversation, newest-first.
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $query = Message::query()
            ->where('conversation_id', $conversation->getKey())
            ->with('reactions')
            ->latest('created_at')
            ->orderByDesc('id');

        return $this->respondPaginated(
            $this->apiPaginate($query, $request, default: 50),
            MessageResource::class,
        );
    }

    /**
     * Send a message through the domain service so the broadcast event fires.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $data = $this->validate($request, [
            'body' => ['required', 'string', 'max:'.(int) config('chat.message_max_length', 4000)],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $parent = null;
        if (! empty($data['parent_id'])) {
            /** @var Message|null $parent */
            $parent = $conversation->messages()->whereKey($data['parent_id'])->first();
        }

        $message = $conversation->send($this->chatUser($request), $data['body'], $parent);

        // send() returns the in-memory model, whose type isn't hydrated with the
        // DB default; reload so the resource reflects the persisted row.
        return $this->respondCreated(MessageResource::make($message->fresh() ?? $message));
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $this->authorize('update', $message);

        $data = $this->validate($request, [
            'body' => ['required', 'string', 'max:'.(int) config('chat.message_max_length', 4000)],
        ]);

        // Persist through save() so the MessageObserver dispatches MessageEdited
        // (keeping broadcasting in sync) and re-syncs mentions for the new body.
        $message->fill(['body' => $data['body'], 'edited_at' => now()])->save();

        return $this->respond(MessageResource::make($message->fresh() ?? $message));
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        // Soft delete via the model so the observer dispatches MessageDeleted.
        $message->delete();

        return $this->respondNoContent();
    }
}
