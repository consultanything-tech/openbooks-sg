<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);
        $taxTotal = round($subtotal * 0.09, 2); // 9% GST
        $discountTotal = 0.00;
        $total = $subtotal + $taxTotal - $discountTotal;

        return [
            'quote_number' => 'QUO-' . fake()->year() . '-' . strtoupper(Str::random(6)),
            'customer_id' => Customer::factory(),
            'quote_date' => now()->subDays(fake()->numberBetween(0, 30)),
            'expiry_date' => now()->addDays(fake()->numberBetween(14, 60)),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $total,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'terms' => 'This quotation is valid for 30 days from the date of issue.',
            'public_token' => Str::random(32),
            'currency_code' => 'SGD',
            'exchange_rate' => 1.000000,
            'converted_invoice_id' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
