<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Ensure Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@openbooks.sg'],
            [
                'name' => 'OpenBooks Administrator',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'is_active' => true,
            ]
        );

        // 2. Company Profile
        DB::table('companies')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'OpenBooks SG',
                'email' => 'accounting@openbooks.sg',
                'phone' => '+65 6789 0123',
                'address' => '1 Raffles Place, Tower One',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'currency_code' => 'SGD',
                'currency_symbol' => 'S$',
                'tax_number' => '202312345A',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Default Categories
        $categories = [
            ['name' => 'Product Sales', 'type' => 'income', 'color' => '#10b981'],
            ['name' => 'Software Consulting', 'type' => 'income', 'color' => '#06b6d4'],
            ['name' => 'Maintenance & Retainers', 'type' => 'income', 'color' => '#3b82f6'],
            ['name' => 'Office Rent & Space', 'type' => 'expense', 'color' => '#ef4444'],
            ['name' => 'Salaries & Wages', 'type' => 'expense', 'color' => '#f59e0b'],
            ['name' => 'Server & Cloud Hosting', 'type' => 'expense', 'color' => '#8b5cf6'],
            ['name' => 'Utilities & Internet', 'type' => 'expense', 'color' => '#ec4899'],
            ['name' => 'Marketing & Advertising', 'type' => 'expense', 'color' => '#f97316'],
            ['name' => 'Office Supplies', 'type' => 'expense', 'color' => '#64748b'],
        ];
        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(['name' => $cat['name']], array_merge($cat, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 4. Default Taxes
        $taxes = [
            ['name' => 'Standard GST (9%)', 'rate' => 9.00, 'type' => 'normal'],
            ['name' => 'Exempt (0%)', 'rate' => 0.00, 'type' => 'normal'],
            ['name' => 'Zero-Rated (0%)', 'rate' => 0.00, 'type' => 'normal'],
        ];
        foreach ($taxes as $tax) {
            DB::table('taxes')->updateOrInsert(['name' => $tax['name']], array_merge($tax, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 5. Default Bank & Cash Accounts
        $bankAccount1 = DB::table('bank_accounts')->updateOrInsert(
            ['name' => 'Primary Operating Account (DBS)'],
            [
                'type' => 'bank',
                'account_number' => '0123456789',
                'bank_name' => 'DBS Bank Ltd',
                'currency' => 'SGD',
                'opening_balance' => 25000.00,
                'current_balance' => 38450.00,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $bankAccount2 = DB::table('bank_accounts')->updateOrInsert(
            ['name' => 'Corporate Expense Card'],
            [
                'type' => 'card',
                'account_number' => '4129-XXXX-XXXX-8812',
                'bank_name' => 'OCBC Bank',
                'currency' => 'SGD',
                'opening_balance' => 5000.00,
                'current_balance' => 3200.00,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $bankAccount3 = DB::table('bank_accounts')->updateOrInsert(
            ['name' => 'Petty Cash Office Drawer'],
            [
                'type' => 'cash',
                'account_number' => 'CASH-001',
                'bank_name' => 'Internal Vault',
                'currency' => 'SGD',
                'opening_balance' => 1500.00,
                'current_balance' => 1120.00,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 6. Default Items (Products & Services)
        $tax18Id = DB::table('taxes')->where('rate', 9.00)->value('id');
        $catSalesId = DB::table('categories')->where('name', 'Product Sales')->value('id');
        $catConsultId = DB::table('categories')->where('name', 'Software Consulting')->value('id');

        $items = [
            [
                'name' => 'Enterprise Cloud ERP License',
                'sku' => 'SW-ERP-ENT',
                'description' => 'Annual enterprise license for core business automation',
                'category_id' => $catSalesId,
                'sale_price' => 2400.00,
                'purchase_price' => 800.00,
                'tax_id' => $tax18Id,
                'unit' => 'license',
            ],
            [
                'name' => 'Senior Architecture Consulting (Hourly)',
                'sku' => 'SRV-ARCH-HR',
                'description' => 'Dedicated engineering and accounting system architecture',
                'category_id' => $catConsultId,
                'sale_price' => 120.00,
                'purchase_price' => 0.00,
                'tax_id' => $tax18Id,
                'unit' => 'hour',
            ],
            [
                'name' => 'Database Migration & Security Audit',
                'sku' => 'SRV-AUDIT-DB',
                'description' => 'Comprehensive database health, indexing, and compliance inspection',
                'category_id' => $catConsultId,
                'sale_price' => 1500.00,
                'purchase_price' => 300.00,
                'tax_id' => $tax18Id,
                'unit' => 'project',
            ],
        ];
        foreach ($items as $item) {
            DB::table('items')->updateOrInsert(['sku' => $item['sku']], array_merge($item, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 7. Default Customers
        $customers = [
            [
                'name' => 'Marina Bay Trading Pte Ltd',
                'email' => 'billing@marinabaytrading.sg',
                'phone' => '+65 6234 5678',
                'company_name' => 'Marina Bay Trading Pte Ltd',
                'tax_number' => '201912345K',
                'address' => '1 Marina Boulevard',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'balance' => 1616.00,
            ],
            [
                'name' => 'Orchard Retail Solutions Pte Ltd',
                'email' => 'accounts@orchardretail.sg',
                'phone' => '+65 6345 6789',
                'company_name' => 'Orchard Retail Solutions Pte Ltd',
                'tax_number' => '202045678B',
                'address' => '391 Orchard Road',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'balance' => 0.00,
            ],
            [
                'name' => 'Changi Logistics Hub Pte Ltd',
                'email' => 'finance@changilogistics.sg',
                'phone' => '+65 6456 7890',
                'company_name' => 'Changi Logistics Hub Pte Ltd',
                'tax_number' => '201834567C',
                'address' => '75 Airport Boulevard',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'balance' => 0.00,
            ],
        ];
        foreach ($customers as $cust) {
            DB::table('customers')->updateOrInsert(['email' => $cust['email']], array_merge($cust, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 8. Default Vendors
        $vendors = [
            [
                'name' => 'CloudServe Technologies',
                'email' => 'billing@cloudserve.example.com',
                'phone' => '+65 6800 0199',
                'company_name' => 'CloudServe Technologies Pte Ltd',
                'tax_number' => '201500001E',
                'address' => '23 Church Street',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'balance' => 840.00,
            ],
            [
                'name' => 'CoWork Spaces SG',
                'email' => 'accounts@coworkspaces.example.com',
                'phone' => '+65 6567 8901',
                'company_name' => 'CoWork Spaces SG Pte Ltd',
                'tax_number' => '201900002D',
                'address' => '71 Robinson Road',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'balance' => 1500.00,
            ],
        ];
        foreach ($vendors as $v) {
            DB::table('vendors')->updateOrInsert(['email' => $v['email']], array_merge($v, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 9. Sample Invoices & Invoice Items
        $custId1 = DB::table('customers')->where('email', 'billing@marinabaytrading.sg')->value('id');
        $custId2 = DB::table('customers')->where('email', 'accounts@orchardretail.sg')->value('id');
        $item1Id = DB::table('items')->where('sku', 'SW-ERP-ENT')->value('id');
        $item2Id = DB::table('items')->where('sku', 'SRV-ARCH-HR')->value('id');

        if ($custId1 && !DB::table('invoices')->where('invoice_number', 'INV-2026-001')->exists()) {
            $invId1 = DB::table('invoices')->insertGetId([
                'invoice_number' => 'INV-2026-001',
                'customer_id' => $custId1,
                'invoice_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->addDays(20)->toDateString(),
                'subtotal' => 2400.00,
                'tax_total' => 216.00,
                'discount_total' => 0.00,
                'total' => 2616.00,
                'paid_amount' => 1000.00,
                'due_amount' => 1616.00,
                'status' => 'partial',
                'notes' => 'Thank you for partnering with OpenBooks SG.',
                'terms' => 'Payment due within 30 days of invoice date.',
                'public_token' => Str::random(32),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
            ]);

            DB::table('invoice_items')->insert([
                'invoice_id' => $invId1,
                'item_id' => $item1Id,
                'name' => 'Enterprise Cloud ERP License',
                'description' => '1 Year Subscription License for 50 Seats',
                'quantity' => 1,
                'price' => 2400.00,
                'tax_rate' => 9.00,
                'tax_amount' => 216.00,
                'total' => 2616.00,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ]);
        }

        if ($custId2 && !DB::table('invoices')->where('invoice_number', 'INV-2026-002')->exists()) {
            $invId2 = DB::table('invoices')->insertGetId([
                'invoice_number' => 'INV-2026-002',
                'customer_id' => $custId2,
                'invoice_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->addDays(25)->toDateString(),
                'subtotal' => 1200.00,
                'tax_total' => 108.00,
                'discount_total' => 50.00,
                'total' => 1258.00,
                'paid_amount' => 1258.00,
                'due_amount' => 0.00,
                'status' => 'paid',
                'notes' => 'Consulting services rendered for Q3 financial sync.',
                'terms' => 'Net 30 terms.',
                'public_token' => Str::random(32),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(1),
            ]);

            DB::table('invoice_items')->insert([
                'invoice_id' => $invId2,
                'item_id' => $item2Id,
                'name' => 'Senior Architecture Consulting',
                'description' => '10 Hours Dedicated Engineering',
                'quantity' => 10,
                'price' => 120.00,
                'tax_rate' => 9.00,
                'tax_amount' => 108.00,
                'total' => 1258.00,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]);
        }

        // 10. Sample Transactions
        $bank1Id = DB::table('bank_accounts')->where('is_default', true)->value('id');
        $catHostingId = DB::table('categories')->where('name', 'Server & Cloud Hosting')->value('id');
        $vendorAwsId = DB::table('vendors')->where('name', 'CloudServe Technologies')->value('id');

        if ($bank1Id && !DB::table('transactions')->exists()) {
            DB::table('transactions')->insert([
                [
                    'type' => 'income',
                    'bank_account_id' => $bank1Id,
                    'to_bank_account_id' => null,
                    'customer_id' => $custId1,
                    'vendor_id' => null,
                    'invoice_id' => 1,
                    'bill_id' => null,
                    'category_id' => $catSalesId,
                    'amount' => 1000.00,
                    'payment_method' => 'bank_transfer',
                    'reference_number' => 'TXN-DBS-99120',
                    'transaction_date' => now()->subDays(5)->toDateString(),
                    'description' => 'Advance payment against INV-2026-001',
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ],
                [
                    'type' => 'income',
                    'bank_account_id' => $bank1Id,
                    'to_bank_account_id' => null,
                    'customer_id' => $custId2,
                    'vendor_id' => null,
                    'invoice_id' => 2,
                    'bill_id' => null,
                    'category_id' => $catConsultId,
                    'amount' => 1258.00,
                    'payment_method' => 'card',
                    'reference_number' => 'STRIPE-CH-882190',
                    'transaction_date' => now()->subDays(1)->toDateString(),
                    'description' => 'Full settlement for INV-2026-002',
                    'created_at' => now()->subDays(1),
                    'updated_at' => now()->subDays(1),
                ],
                [
                    'type' => 'expense',
                    'bank_account_id' => $bank1Id,
                    'to_bank_account_id' => null,
                    'customer_id' => null,
                    'vendor_id' => $vendorAwsId,
                    'invoice_id' => null,
                    'bill_id' => null,
                    'category_id' => $catHostingId,
                    'amount' => 450.00,
                    'payment_method' => 'card',
                    'reference_number' => 'CS-INV-77124',
                    'transaction_date' => now()->subDays(3)->toDateString(),
                    'description' => 'Cloud hosting and storage services - September',
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ]
            ]);
        }
    }
}
