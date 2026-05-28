<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Chat;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Chat\Models\Message;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /** @var class-string<Message> */
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->sentence(),
        ];
    }
}
