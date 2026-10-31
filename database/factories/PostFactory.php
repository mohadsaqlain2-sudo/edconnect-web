<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Short enough to stay well under the 280 character limit.
            'body' => fake()->sentence(random_int(6, 16)),
            'image' => null,
            'is_hidden' => false,
        ];
    }

    /**
     * A post that an administrator has hidden (FR-27).
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
