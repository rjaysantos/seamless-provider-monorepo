<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branchId' => $this->faker->randomNumber(),
            'playId' => $this->faker->word(),
            'memberId' => $this->faker->randomNumber(),
            'username' => $this->faker->userName(),
            'currency' => $this->faker->currencyCode(),
            'language' => $this->faker->languageCode(),
            'country' => $this->faker->countryCode(),
            'gameId' => $this->faker->randomNumber(),
            'host' => $this->faker->url(),
            'device' => $this->faker->numberBetween(0, 1),
            'isTrial' => $this->faker->numberBetween(0, 1),
            'balance' => $this->faker->randomNumber(),
            'wcUserId' => $this->faker->randomNumber(),
            'wcStatus' => $this->faker->numberBetween(0, 1),
            'wcUserName' => $this->faker->userName(),
            'wcSID' => $this->faker->word(),
            'token' => $this->faker->word()
        ];
    }
}
