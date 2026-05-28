<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Kurt\Modules\Chat\Models\Message;

final class MessagePolicy
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

    public function view(?Authenticatable $user, Message $message): bool
    {
        if ($user === null) {
            return false;
        }

        return $message->conversation->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    public function update(Authenticatable $user, Message $message): bool
    {
        if ((int) $user->getAuthIdentifier() !== (int) $message->user_id) {
            return false;
        }

        return $this->withinEditWindow($message);
    }

    public function delete(Authenticatable $user, Message $message): bool
    {
        if ((int) $user->getAuthIdentifier() !== (int) $message->user_id) {
            return false;
        }

        return $this->withinEditWindow($message);
    }

    private function withinEditWindow(Message $message): bool
    {
        $minutes = (int) config('chat.edit_window_minutes', 15);
        if ($minutes <= 0) {
            return true;
        }

        /** @var Carbon $createdAt */
        $createdAt = $message->created_at;

        return $createdAt->copy()->addMinutes($minutes)->isFuture();
    }
}
