<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Models\Mention;

/**
 * @extends Factory<Mention>
 */
class MentionFactory extends Factory
{
    /** @var class-string<Mention> */
    protected $model = Mention::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seen_at' => null,
        ];
    }
}
