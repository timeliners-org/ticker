<?php

namespace Database\Factories;

use App\Models\ShiftCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftCategory>
 */
class ShiftCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
            'owner_id' => null,
        ];
    }
}
