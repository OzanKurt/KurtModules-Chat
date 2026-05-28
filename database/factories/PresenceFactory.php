<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Enums\PresenceStatus;
use Kurt\Modules\Chat\Models\Presence;

/**
 * @extends Factory<Presence>
 */
class PresenceFactory extends Factory
{
    /** @var class-string<Presence> */
    protected $model = Presence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => PresenceStatus::Online,
            'heartbeat_at' => now(),
        ];
    }

    public function status(PresenceStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
