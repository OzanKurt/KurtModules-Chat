<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Http\Concerns\InteractsWithChatUser;
use Kurt\Modules\Chat\Http\Resources\ConversationResource;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;

final class ConversationController extends ApiController
{
    use HandlesApiQuery;
    use InteractsWithChatUser;
    use ResolvesUser;

    /**
     * List the authenticated user's conversations, most-recently-active first.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $this->chatUser($request)->getKey();

        $query = Conversation::query()
            ->withCount('participants')
            ->whereHas('participants', fn (Builder $p) => $p->where('user_id', $userId));

        $query = $this->applyApiFilters($query, $request, ['type' => 'exact', 'visibility' => 'exact']);

        // Default to most-recent-activity first; an explicit ?sort overrides it.
        if (! is_string($request->query('sort')) || $request->query('sort') === '') {
            $query->orderByDesc('last_message_at')->orderByDesc('id');
        } else {
            $query = $this->applyApiSorts($query, $request, ['last_message_at', 'created_at', 'name']);
        }

        return $this->respondPaginated(
            $this->apiPaginate($query, $request),
            ConversationResource::class,
        );
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadCount('participants');

        return $this->respond(ConversationResource::make($conversation));
    }

    /**
     * Create a room, or resolve/create a direct conversation with another user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validate($request, [
            'type' => ['required', Rule::enum(ConversationType::class)],
            'name' => ['required_if:type,room', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'user_id' => ['required_if:type,direct', 'nullable'],
            'participant_ids' => ['sometimes', 'array'],
            'participant_ids.*' => ['required'],
        ]);

        $actor = $this->chatUser($request);

        if ($data['type'] === ConversationType::Direct->value) {
            $other = $this->userResolver()->newQuery()->whereKey($data['user_id'])->first();

            if ($other === null) {
                return $this->fail('The selected user could not be found.', 422, [
                    'user_id' => ['The selected user could not be found.'],
                ]);
            }

            $conversation = Conversation::directBetween($actor, $other);

            return $this->respondCreated(ConversationResource::make($conversation->loadCount('participants')));
        }

        $conversation = Conversation::query()->create([
            'type' => ConversationType::Room,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'visibility' => ConversationVisibility::Private,
            'created_by' => $actor->getKey(),
        ]);

        $conversation->participants()->create([
            'user_id' => $actor->getKey(),
            'role' => ParticipantRole::Owner->value,
            'joined_at' => now(),
            'notifications' => ParticipantNotifications::All->value,
        ]);

        foreach (array_unique($data['participant_ids'] ?? []) as $participantId) {
            if ((string) $participantId === (string) $actor->getKey()) {
                continue;
            }

            $conversation->participants()->create([
                'user_id' => $participantId,
                'role' => ParticipantRole::Member->value,
                'joined_at' => now(),
                'notifications' => ParticipantNotifications::All->value,
            ]);
        }

        return $this->respondCreated(ConversationResource::make($conversation->loadCount('participants')));
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('manageRoom', $conversation);

        $data = $this->validate($request, [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $conversation->fill($data)->save();

        return $this->respond(ConversationResource::make($conversation->loadCount('participants')));
    }

    /**
     * Leave the conversation: remove the authenticated user's participant row.
     */
    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->participants()
            ->where('user_id', $this->chatUser($request)->getKey())
            ->delete();

        return $this->respondNoContent();
    }
}
