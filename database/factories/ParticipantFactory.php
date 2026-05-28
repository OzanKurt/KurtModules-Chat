<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Participant;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /** @var class-string<Participant> */
    protected $model = Participant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => ParticipantRole::Member,
            'joined_at' => now(),
            'notifications' => ParticipantNotifications::All,
        ];
    }

    public function role(ParticipantRole $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }
}
