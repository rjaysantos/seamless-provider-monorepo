<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class SboReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bet_id' => $this->faker->word(),
            'trx_id' => $this->faker->word(),
            'play_id' => $this->faker->word(),
            'web_id' => $this->faker->randomDigit(),
            'currency' => $this->faker->currencyCode(),
            'bet_amount' => $this->faker->randomDigit(),
            'payout_amount' => $this->faker->randomDigit(),
            'bet_time' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'bet_choice' => $this->faker->word(),
            'game_code' => 1,
            'sports_type' => $this->faker->word(),
            'event' => $this->faker->word(),
            'match' => $this->faker->word(),
            'hdp' => $this->faker->word(),
            'odds' => $this->faker->randomDigit(),
            'result' => 'lose',
            'flag' => 'running',
            'status' => 0,
            'created_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'updated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s')
        ];
    }
}
