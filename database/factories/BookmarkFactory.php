<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bookmark>
 */
class BookmarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(4, asText: true),
            'url' => 'https://laravel.com',
            'favorite' => false,
            'user_id' => User::factory(),
        ];
    }

    public function favorite(): Factory
    {
        return $this->state(function () {
            return [
                'favorite' => true
            ];
        });
    }
}
