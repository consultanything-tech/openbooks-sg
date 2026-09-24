<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Payment factory — creates Transaction records of type "income"
 * (the app models payments as Transaction with type income).
 *
 * @extends Factory<Transaction>
 */
class PaymentFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'type' => 'income',
            'bank_account_id' => BankAccount::factory(),
            'to_bank_account_id' => null,
            'customer_id' => null,
            'vendor_id' => null,
            'invoice_id' => null,
            'bill_id' => null,
            'category_id' => null,
            'amount' => fake()->randomFloat(2, 50, 20000),
            'payment_method' => fake()->randomElement([
                'bank_transfer', 'paynow', 'cheque', 'cash', 'credit_card',
            ]),
            'reference_number' => 'PAY-'.strtoupper(fake()->bothify('########')),
            'transaction_date' => now()->subDays(fake()->numberBetween(0, 30)),
            'description' => fake()->optional()->sentence(),
            'is_reconciled' => false,
            'reconciled_at' => null,
        ];
    }

    /**
     * Payment against a specific invoice.
     */
    public function forInvoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => Invoice::factory(),
        ]);
    }

    /**
     * Payment to a specific bill (expense type).
     */
    public function forBill(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'bill_id' => Bill::factory(),
            'vendor_id' => Vendor::factory(),
        ]);
    }

    public function reconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }
}
