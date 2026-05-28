<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;

final class ConversationPolicy
{
    public function before(?Authenticatable $user, string $ability): ?bool
    {
        if ($user === null) {
            return null;
        }

        /** @var Gate $gate */
        $gate = app(Gate::class);
        if ($gate->forUser($user)->has('canModerateChat') && $gate->forUser($user)->allows('canModerateChat')) {
            return true;
        }

        return null;
    }

    public function view(?Authenticatable $user, Conversation $conversation): bool
    {
        if ($user === null) {
            return false;
        }

        return $conversation->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    public function sendMessage(Authenticatable $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    public function react(Authenticatable $user, Conversation $conversation): bool
    {
        return $this->sendMessage($user, $conversation);
    }

    public function manageRoom(Authenticatable $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('role', [ParticipantRole::Owner->value, ParticipantRole::Admin->value])
            ->exists();
    }
}
