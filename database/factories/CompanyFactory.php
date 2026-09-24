<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+65 #### ####'),
            'address' => fake()->streetAddress().', #'.fake()->numberBetween(1, 30).'-'.fake()->numberBetween(1, 200),
            'city' => 'Singapore',
            'state' => 'Singapore',
            'country' => 'Singapore',
            'currency_code' => 'SGD',
            'currency_symbol' => 'S$',
            'financial_year' => 'January - December',
            'financial_year_start' => '01-01',
            'tax_number' => fake()->numerify('20#########').strtoupper(fake()->randomLetter()),
            'logo_path' => null,
            'invoice_prefix' => 'INV',
            'bill_prefix' => 'BILL',
            'credit_note_prefix' => 'CN',
            'accent_color' => '#4f46e5',
            'show_logo_on_documents' => true,
            'show_tax_number_on_documents' => true,
            'show_phone_on_documents' => true,
        ];
    }
}
