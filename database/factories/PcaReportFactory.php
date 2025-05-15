<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class PcaReportFactory extends Factory
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
            'currency' => $this->faker->currencyCode(),
            'game_code' => $this->faker->word() . ';' . $this->faker->word(),
            'bet_choice' => '-',
            'bet_id' => $this->faker->word(),
            'bet_time' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'wager_amount' => $this->faker->randomNumber(),
            'payout_amount' => $this->faker->randomNumber(),
            'status' => $this->faker->randomElement(['WAGER', 'PAYOUT', 'REFUND']),
            'ref_id' => (string) $this->faker->randomNumber(5)
        ];
    }
}
