<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+65 9### ####'),
            'company_name' => fake()->company() . ' Pte Ltd',
            'tax_number' => fake()->numerify('20#########') . strtoupper(fake()->randomLetter()),
            'address' => fake()->streetAddress() . ', #' . fake()->numberBetween(1, 30) . '-' . fake()->numberBetween(1, 200),
            'city' => 'Singapore',
            'country' => 'Singapore',
            'currency' => 'SGD',
            'balance' => 0.00,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
