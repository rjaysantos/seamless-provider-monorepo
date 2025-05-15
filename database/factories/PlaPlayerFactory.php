<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlaPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id_provider' => $this->faker->randomNumber(),
            'play_id' => $this->faker->word(),
            'username' => $this->faker->userName(),
            'currency' => $this->faker->currencyCode(),
        ];
    }
}
