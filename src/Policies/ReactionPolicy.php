<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;

final class ReactionPolicy
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

    public function delete(Authenticatable $user, Reaction $reaction): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $reaction->user_id;
    }
}
