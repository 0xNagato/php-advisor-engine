<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'message' => $this->faker->paragraph(),
            'call_to_action_title' => $this->faker->words(3, true),
            'call_to_action_url' => $this->faker->url(),
        ];
    }
}
