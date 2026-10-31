<?php

namespace Database\Factories;

use App\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Hashtag>
 */
class HashtagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Hashtags are stored in lower case, without the leading "#".
            'name' => Str::lower(fake()->unique()->word()),
        ];
    }
}
