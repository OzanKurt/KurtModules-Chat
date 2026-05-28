<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Contracts;

interface ChatParticipant
{
    /**
     * Return the user's primary key — mirrors Eloquent's `getKey()` so
     * `User extends Model implements ChatParticipant` stays compatible.
     *
     * @return int|string
     */
    public function getKey();

    public function getChatDisplayName(): string;

    public function getChatAvatarUrl(): ?string;
}
