<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wcUserId' => $this->faker->randomDigit(),
            'debitAmount' => $this->faker->randomNumber(),
            'creditAmount' => $this->faker->randomNumber(),
            'bonusAmount' => $this->faker->randomNumber(),
            'wcPrdId' => $this->faker->randomNumber(),
            'wcTxnId' => $this->faker->word(),
            'wcDebitRoundId' => $this->faker->word(),
            'wcCreditRoundId' => $this->faker->word(),
            'wcGameId' => $this->faker->randomNumber(),
            'wcTableId' => $this->faker->word(),
            'wcBonusType' => $this->faker->randomNumber(),
            'wcCreditType' => $this->faker->randomNumber(),
            'wcDebitTime' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'wcCreditTime' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'wcBonusTime' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];
    }
}
