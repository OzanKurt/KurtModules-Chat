<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Models\Conversation;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /** @var class-string<Conversation> */
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ConversationType::Room,
            'name' => $this->faker->unique()->words(2, true),
            'visibility' => ConversationVisibility::Private,
        ];
    }

    public function room(): static
    {
        return $this->state(fn () => ['type' => ConversationType::Room]);
    }

    public function direct(): static
    {
        return $this->state(fn () => [
            'type' => ConversationType::Direct,
            'name' => null,
            'visibility' => ConversationVisibility::Private,
        ]);
    }

    public function visibility(ConversationVisibility $visibility): static
    {
        return $this->state(fn () => ['visibility' => $visibility]);
    }
}
