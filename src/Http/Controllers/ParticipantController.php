<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Http\Resources\ParticipantResource;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Participant;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;

final class ParticipantController extends ApiController
{
    use HandlesApiQuery;

    /**
     * List a conversation's participants (participant-scoped read).
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $query = Participant::query()
            ->where('conversation_id', $conversation->getKey())
            ->orderBy('id');

        return $this->respondPaginated(
            $this->apiPaginate($query, $request, default: 50),
            ParticipantResource::class,
        );
    }

    /**
     * Add a user to the conversation (owners/admins only).
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('manageRoom', $conversation);

        $data = $this->validate($request, [
            'user_id' => ['required'],
            'role' => ['sometimes', Rule::enum(ParticipantRole::class)],
        ]);

        $participant = $conversation->participants()->firstOrCreate(
            ['user_id' => $data['user_id']],
            [
                'role' => $data['role'] ?? ParticipantRole::Member->value,
                'joined_at' => now(),
                'notifications' => ParticipantNotifications::All->value,
            ],
        );

        return $this->respondCreated(ParticipantResource::make($participant));
    }

    /**
     * Remove a user from the conversation (owners/admins only).
     */
    public function destroy(Request $request, Conversation $conversation, string $user): JsonResponse
    {
        $this->authorize('manageRoom', $conversation);

        $conversation->participants()
            ->where('user_id', $user)
            ->delete();

        return $this->respondNoContent();
    }
}
