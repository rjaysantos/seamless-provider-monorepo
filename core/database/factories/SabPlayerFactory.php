<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class SabPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'play_id' => $this->faker->word(),
            'username' => $this->faker->userName(),
            'currency' => $this->faker->currencyCode(),
            'game' => 0,
            'count' => 0,
            'limit' => 0,
        ];
    }
}
