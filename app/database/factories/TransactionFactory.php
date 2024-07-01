<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Log;

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
    public function definition()
    {
        return [
            'credit' => $this->faker->boolean(),
            'account_id' => Account::factory(),
            'transaction_date' => $this->faker->date(),
            'amount' => $this->faker->randomDigit(),
            'note' => $this->faker->text(),
        ];
    }
}
