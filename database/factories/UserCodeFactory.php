<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Random\RandomException;

class UserCodeFactory extends Factory
{
    protected $model = UserCode::class;

    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            'code' => random_int(100000, 999999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
