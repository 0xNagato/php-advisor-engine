<?php

namespace Database\Factories;

use App\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TokenFactory extends Factory
{
    protected $model = Token::class;

    public function definition(): array
    {
        return [
            'tokenable_type' => $this->faker->word(),
            'tokenable_id' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'abilities' => $this->faker->words(),
            'token' => Str::random(10),
            'last_used_at' => Carbon::now(),
            'expires_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'name' => $this->faker->name(),
        ];
    }
}
