<?php

namespace Database\Factories;

use App\Models\Quota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quota>
 */
class QuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'quota' => $this->faker->numberBetween(1, 24 * 60 * 60),
            'user_id' => User::factory(),
        ];
    }
}
