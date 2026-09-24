<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $salePrice = fake()->randomFloat(2, 10, 5000);
        $purchasePrice = round($salePrice * 0.6, 2);

        return [
            'name' => fake()->randomElement([
                'Web Development Service', 'Cloud Hosting Plan',
                'IT Consultation (1hr)', 'Software License - Annual',
                'Network Setup & Configuration', 'Data Backup Service',
                'Cybersecurity Audit', 'Mobile App Development',
                'Server Maintenance (Monthly)', 'Technical Support Plan',
            ]),
            'sku' => 'SKU-' . strtoupper(fake()->bothify('??####')),
            'description' => fake()->optional()->sentence(),
            'category_id' => null,
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'tax_id' => null,
            'unit' => fake()->randomElement(['unit', 'hour', 'month', 'year', 'project']),
            'is_active' => true,
            'stock_quantity' => 0,
            'reorder_level' => 0,
            'cost_price' => $purchasePrice,
            'track_inventory' => false,
        ];
    }

    public function inventoryTracked(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => true,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'reorder_level' => fake()->numberBetween(5, 50),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
