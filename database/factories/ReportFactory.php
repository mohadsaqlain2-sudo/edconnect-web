<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * A report against a post by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->sentence(8),
            'status' => Report::STATUS_PENDING,
        ];
    }

    /**
     * Report a post.
     */
    public function forPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
        ]);
    }

    /**
     * Report a comment.
     */
    public function forComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Comment::class,
            'reportable_id' => Comment::factory(),
        ]);
    }

    /**
     * A report that an administrator has already looked at.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_REVIEWED,
        ]);
    }

    /**
     * A report that was rejected.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_DISMISSED,
        ]);
    }
}
