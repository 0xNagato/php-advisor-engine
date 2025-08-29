<?php

namespace Database\Factories;

use App\Models\Concierge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConciergeFactory extends Factory
{
    protected $model = Concierge::class;

    public function definition(): array
    {
        return [
            'hotel_name' => fake()->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }

    public function branded(): static
    {
        return $this->state(fn (array $attributes) => [
            'branding' => [
                'brand_name' => 'Sample Brand',
                'description' => 'Welcome to our exclusive booking experience',
                'logo_url' => app()->environment().'/concierges/logos/sample-logo.png',
                'main_color' => '#3B82F6',
                'secondary_color' => '#1E40AF',
                'gradient_start' => '#3B82F6',
                'gradient_end' => '#1E40AF',
                'text_color' => '#1F2937',
                'redirect_url' => 'https://example.com/thank-you',
            ],
        ]);
    }

    public function qr(int $percentage = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'is_qr_concierge' => true,
            'revenue_percentage' => $percentage,
        ]);
    }
}
