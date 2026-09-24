<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);
        $taxTotal = round($subtotal * 0.09, 2); // 9% GST
        $discountTotal = 0.00;
        $total = $subtotal + $taxTotal - $discountTotal;

        return [
            'invoice_number' => 'INV-' . fake()->year() . '-' . strtoupper(Str::random(6)),
            'customer_id' => Customer::factory(),
            'invoice_date' => now()->subDays(fake()->numberBetween(0, 60)),
            'due_date' => now()->addDays(fake()->numberBetween(14, 30)),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $total,
            'paid_amount' => 0.00,
            'due_amount' => $total,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'terms' => 'Payment due within 30 days. Late payment subject to 1.5% monthly interest.',
            'public_token' => Str::random(32),
            'currency_code' => 'SGD',
            'exchange_rate' => 1.000000,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'];
            return [
                'status' => 'paid',
                'paid_amount' => $total,
                'due_amount' => 0.00,
            ];
        });
    }

    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total'];
            $paid = round($total * 0.5, 2);
            return [
                'status' => 'partial',
                'paid_amount' => $paid,
                'due_amount' => round($total - $paid, 2),
            ];
        });
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
