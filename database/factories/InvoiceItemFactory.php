<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $price = fake()->randomFloat(2, 10, 5000);
        $taxRate = fake()->randomElement([0, 9.00]); // 0% or 9% GST
        $subtotal = round($quantity * $price, 2);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        return [
            'invoice_id' => Invoice::factory(),
            'item_id' => null,
            'name' => fake()->randomElement([
                'Consulting Services', 'Web Development', 'UI/UX Design',
                'Cloud Hosting (Monthly)', 'IT Support Retainer',
                'Software License', 'Data Migration Service',
                'System Integration', 'Training Session',
            ]),
            'description' => fake()->optional()->sentence(),
            'quantity' => $quantity,
            'price' => $price,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ];
    }
}
