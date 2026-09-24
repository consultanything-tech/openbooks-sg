<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 30000);
        $taxTotal = round($subtotal * 0.09, 2); // 9% GST
        $discountTotal = 0.00;
        $total = $subtotal + $taxTotal - $discountTotal;

        return [
            'bill_number' => 'BILL-' . fake()->year() . '-' . strtoupper(Str::random(6)),
            'vendor_id' => Vendor::factory(),
            'bill_date' => now()->subDays(fake()->numberBetween(0, 60)),
            'due_date' => now()->addDays(fake()->numberBetween(14, 30)),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $total,
            'paid_amount' => 0.00,
            'due_amount' => $total,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
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

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
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
