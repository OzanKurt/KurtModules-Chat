<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Models\Reaction;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    /** @var class-string<Reaction> */
    protected $model = Reaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'emoji' => $this->faker->randomElement(['👍', '🎉', '❤️', '🚀']),
        ];
    }
}
