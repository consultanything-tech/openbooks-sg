<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'DBS Business Account', 'OCBC Current Account',
                'UOB Operating Account', 'Maybank Business Account',
                'Petty Cash', 'Stripe Payout Account',
            ]),
            'type' => fake()->randomElement(['bank', 'cash', 'card']),
            'account_number' => fake()->numerify('##########'),
            'bank_name' => fake()->randomElement([
                'DBS Bank', 'OCBC Bank', 'UOB', 'Maybank', 'Standard Chartered',
            ]),
            'currency' => 'SGD',
            'opening_balance' => fake()->randomFloat(2, 0, 100000),
            'current_balance' => fake()->randomFloat(2, 0, 100000),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
