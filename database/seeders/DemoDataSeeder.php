<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\RecurringTemplate;
use App\Models\RecurringTemplateItem;
use App\Models\Tax;
use App\Models\TimeEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed the application with comprehensive demo data for Singapore.
     * Run: php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        // Skip if demo data already exists
        if (Company::where('name', 'Acme Pte Ltd')->exists()) {
            $this->command->warn('Demo data already exists (Acme Pte Ltd found). Skipping.');
            return;
        }

        DB::transaction(function () {
            $this->createCompany();
            $users = $this->createUsers();
            $tax = $this->createTaxes();
            $categories = $this->createCategories();
            $bankAccounts = $this->createBankAccounts();
            $items = $this->createItems($tax, $categories);
            $customers = $this->createCustomers();
            $vendors = $this->createVendors();
            $invoices = $this->createInvoices($customers, $items);
            $bills = $this->createBills($vendors, $items);
            $this->createQuotes($customers, $items);
            $this->createTransactions($bankAccounts, $customers, $vendors, $invoices, $bills, $categories);
            $this->createCreditNotes($customers, $invoices, $items);
            $this->createJournalEntries($users['admin']);
            $this->createExpenseClaims($users, $categories);
            $this->createTimeEntries($users, $customers);
            $this->createBudgets($categories);
            $this->createRecurringTemplates($customers, $vendors, $items);
        });

        $this->command->info('Demo data seeded successfully.');
    }

    // --- SECTION: Company ---
    private function createCompany(): Company
    {
        return Company::create([
            'name' => 'Acme Pte Ltd',
            'email' => 'finance@acme.sg',
            'phone' => '+65 6123 4567',
            'address' => '10 Anson Road, #22-08 International Plaza',
            'city' => 'Singapore',
            'state' => 'Singapore',
            'country' => 'Singapore',
            'currency_code' => 'SGD',
            'currency_symbol' => 'S$',
            'tax_number' => '202412345A',
            'financial_year' => 'January - December',
            'financial_year_start' => '01-01',
            'invoice_prefix' => 'INV',
            'bill_prefix' => 'BILL',
            'credit_note_prefix' => 'CN',
            'accent_color' => '#4f46e5',
            'show_logo_on_documents' => true,
            'show_tax_number_on_documents' => true,
            'show_phone_on_documents' => true,
            'paynow_id' => '202412345A',
            'paynow_id_type' => 'UEN',
            'paynow_name' => 'Acme Pte Ltd',
            'default_payment_terms' => 'Net 30',
            'default_payment_notes' => 'Please quote the invoice number when making payment.',
            'invoice_footer' => 'Thank you for your business. Payment via PayNow, bank transfer, or cheque.',
        ]);
    }

    // --- SECTION: Users ---
    private function createUsers(): array
    {
        $admin = User::create([
            'name' => 'Tan Wei Ming',
            'email' => 'admin@demo.test',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $accountant = User::create([
            'name' => 'Lim Siew Hoon',
            'email' => 'accountant@demo.test',
            'password' => Hash::make('password'),
            'role' => 'ACCOUNTANT',
            'is_active' => true,
        ]);

        $viewer = User::create([
            'name' => 'Rajesh Kumar',
            'email' => 'viewer@demo.test',
            'password' => Hash::make('password'),
            'role' => 'VIEWER',
            'is_active' => true,
        ]);

        return ['admin' => $admin, 'accountant' => $accountant, 'viewer' => $viewer];
    }

    // --- SECTION: Taxes ---
    private function createTaxes(): Tax
    {
        Tax::firstOrCreate(['name' => 'Standard GST (9%)'], [
            'rate' => 9.00,
            'type' => 'normal',
            'is_active' => true,
        ]);

        Tax::firstOrCreate(['name' => 'Exempt (0%)'], [
            'rate' => 0.00,
            'type' => 'normal',
            'is_active' => true,
        ]);

        return Tax::where('name', 'Standard GST (9%)')->first();
    }

    // --- SECTION: Categories ---
    private function createCategories(): array
    {
        $cats = [
            ['name' => 'Product Sales', 'type' => 'income', 'color' => '#10b981'],
            ['name' => 'Consulting Services', 'type' => 'income', 'color' => '#06b6d4'],
            ['name' => 'Subscription Revenue', 'type' => 'income', 'color' => '#3b82f6'],
            ['name' => 'Office Rent', 'type' => 'expense', 'color' => '#ef4444'],
            ['name' => 'Salaries & CPF', 'type' => 'expense', 'color' => '#f59e0b'],
            ['name' => 'Cloud & Hosting', 'type' => 'expense', 'color' => '#8b5cf6'],
            ['name' => 'Utilities & Telecom', 'type' => 'expense', 'color' => '#ec4899'],
            ['name' => 'Marketing', 'type' => 'expense', 'color' => '#f97316'],
            ['name' => 'Professional Fees', 'type' => 'expense', 'color' => '#64748b'],
            ['name' => 'Travel & Transport', 'type' => 'expense', 'color' => '#14b8a6'],
        ];

        foreach ($cats as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        return Category::pluck('id', 'name')->toArray();
    }

    // --- SECTION: Bank Accounts ---
    private function createBankAccounts(): array
    {
        $dbs = BankAccount::create([
            'name' => 'DBS Business Current',
            'type' => 'bank',
            'account_number' => '012-000-345678',
            'bank_name' => 'DBS Bank Ltd',
            'branch_name' => 'Tanjong Pagar Branch',
            'currency' => 'SGD',
            'opening_balance' => 150000.00,
            'current_balance' => 187450.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $ocbc = BankAccount::create([
            'name' => 'OCBC Business Savings',
            'type' => 'bank',
            'account_number' => '501-234567-001',
            'bank_name' => 'OCBC Bank',
            'branch_name' => 'Raffles Place Branch',
            'currency' => 'SGD',
            'opening_balance' => 80000.00,
            'current_balance' => 92300.00,
            'is_default' => false,
            'status' => 'active',
        ]);

        $uob = BankAccount::create([
            'name' => 'UOB Corporate Account',
            'type' => 'bank',
            'account_number' => '302-456789-0',
            'bank_name' => 'United Overseas Bank',
            'branch_name' => 'Jurong East Branch',
            'currency' => 'SGD',
            'opening_balance' => 45000.00,
            'current_balance' => 51200.00,
            'is_default' => false,
            'status' => 'active',
        ]);

        return ['dbs' => $dbs, 'ocbc' => $ocbc, 'uob' => $uob];
    }

    // --- SECTION: Items ---
    private function createItems(Tax $tax, array $categories): array
    {
        $itemsData = [
            ['name' => 'Cloud ERP License (Annual)', 'sku' => 'SW-ERP-001', 'description' => 'Annual enterprise ERP subscription for 50 users', 'sale_price' => 4800.00, 'purchase_price' => 1600.00, 'unit' => 'license', 'category' => 'Product Sales'],
            ['name' => 'Web Development (Hourly)', 'sku' => 'SRV-WEB-001', 'description' => 'Full-stack web development services', 'sale_price' => 150.00, 'purchase_price' => 0.00, 'unit' => 'hour', 'category' => 'Consulting Services'],
            ['name' => 'Mobile App Development', 'sku' => 'SRV-MOB-001', 'description' => 'iOS and Android app development', 'sale_price' => 180.00, 'purchase_price' => 0.00, 'unit' => 'hour', 'category' => 'Consulting Services'],
            ['name' => 'IT Consultation (1hr)', 'sku' => 'SRV-CON-001', 'description' => 'Senior IT architecture consultation', 'sale_price' => 200.00, 'purchase_price' => 0.00, 'unit' => 'hour', 'category' => 'Consulting Services'],
            ['name' => 'Cybersecurity Audit', 'sku' => 'SRV-SEC-001', 'description' => 'Comprehensive security assessment and penetration testing', 'sale_price' => 3500.00, 'purchase_price' => 800.00, 'unit' => 'project', 'category' => 'Consulting Services'],
            ['name' => 'Cloud Hosting (Monthly)', 'sku' => 'SRV-HST-001', 'description' => 'Managed cloud hosting with 99.9% SLA', 'sale_price' => 450.00, 'purchase_price' => 200.00, 'unit' => 'month', 'category' => 'Subscription Revenue'],
            ['name' => 'Data Backup Service', 'sku' => 'SRV-BKP-001', 'description' => 'Automated daily backup with geo-redundancy', 'sale_price' => 120.00, 'purchase_price' => 40.00, 'unit' => 'month', 'category' => 'Subscription Revenue'],
            ['name' => 'Technical Support Plan', 'sku' => 'SRV-SUP-001', 'description' => 'Priority technical support, 24/7 coverage', 'sale_price' => 800.00, 'purchase_price' => 300.00, 'unit' => 'month', 'category' => 'Subscription Revenue'],
            ['name' => 'Network Setup & Config', 'sku' => 'SRV-NET-001', 'description' => 'Office network installation and configuration', 'sale_price' => 2200.00, 'purchase_price' => 900.00, 'unit' => 'project', 'category' => 'Consulting Services'],
            ['name' => 'Server Maintenance', 'sku' => 'SRV-MNT-001', 'description' => 'Monthly server patching and health checks', 'sale_price' => 350.00, 'purchase_price' => 120.00, 'unit' => 'month', 'category' => 'Subscription Revenue'],
            ['name' => 'Laptop - ThinkPad T14s', 'sku' => 'HW-LAP-001', 'description' => 'Lenovo ThinkPad T14s Gen 4, i7, 16GB RAM', 'sale_price' => 2100.00, 'purchase_price' => 1500.00, 'unit' => 'unit', 'category' => 'Product Sales', 'track_inventory' => true, 'stock_quantity' => 12, 'reorder_level' => 3],
            ['name' => 'Monitor - Dell U2723QE', 'sku' => 'HW-MON-001', 'description' => 'Dell 27-inch 4K USB-C Hub Monitor', 'sale_price' => 780.00, 'purchase_price' => 550.00, 'unit' => 'unit', 'category' => 'Product Sales', 'track_inventory' => true, 'stock_quantity' => 8, 'reorder_level' => 2],
            ['name' => 'Docking Station', 'sku' => 'HW-DCK-001', 'description' => 'Universal USB-C docking station with dual display', 'sale_price' => 320.00, 'purchase_price' => 180.00, 'unit' => 'unit', 'category' => 'Product Sales', 'track_inventory' => true, 'stock_quantity' => 25, 'reorder_level' => 5],
            ['name' => 'GST Filing Service', 'sku' => 'SRV-GST-001', 'description' => 'Quarterly GST F5 preparation and filing with IRAS', 'sale_price' => 600.00, 'purchase_price' => 0.00, 'unit' => 'quarter', 'category' => 'Professional Fees'],
            ['name' => 'Annual Audit Support', 'sku' => 'SRV-AUD-001', 'description' => 'Year-end audit preparation and liaison', 'sale_price' => 2800.00, 'purchase_price' => 0.00, 'unit' => 'year', 'category' => 'Professional Fees'],
        ];

        $items = [];
        foreach ($itemsData as $data) {
            $catName = $data['category'] ?? null;
            unset($data['category']);
            $data['category_id'] = $categories[$catName] ?? null;
            $data['tax_id'] = $tax->id;
            $data['is_active'] = true;
            $data['track_inventory'] = $data['track_inventory'] ?? false;
            $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
            $data['reorder_level'] = $data['reorder_level'] ?? 0;
            $data['cost_price'] = $data['purchase_price'];
            $items[] = Item::create($data);
        }

        return $items;
    }

    // --- SECTION: Customers ---
    private function createCustomers(): array
    {
        $customersData = [
            ['name' => 'Wong Chee Keong', 'email' => 'ck.wong@marinabay.sg', 'phone' => '+65 9123 4567', 'company_name' => 'Marina Bay Holdings Pte Ltd', 'tax_number' => '201834567K', 'address' => '1 Marina Boulevard, #28-00', 'city' => 'Singapore'],
            ['name' => 'Nurul Aisyah Binte Rahman', 'email' => 'aisyah@orchardretail.sg', 'phone' => '+65 8234 5678', 'company_name' => 'Orchard Retail Group Pte Ltd', 'tax_number' => '201945678B', 'address' => '391 Orchard Road, #15-01 Ngee Ann City', 'city' => 'Singapore'],
            ['name' => 'Chan Wei Lun', 'email' => 'wl.chan@changilogistics.sg', 'phone' => '+65 9345 6789', 'company_name' => 'Changi Logistics Hub Pte Ltd', 'tax_number' => '201756789C', 'address' => '75 Airport Boulevard, #03-12', 'city' => 'Singapore'],
            ['name' => 'Priya Nair', 'email' => 'priya@tanjongpagar.sg', 'phone' => '+65 8456 7890', 'company_name' => 'Tanjong Pagar Trading Co', 'tax_number' => '202067890D', 'address' => '8 Eu Tong Sen Street, #20-91', 'city' => 'Singapore'],
            ['name' => 'Lee Hsien Wei', 'email' => 'hw.lee@jurongtech.sg', 'phone' => '+65 9567 8901', 'company_name' => 'Jurong Tech Solutions Pte Ltd', 'tax_number' => '201678901E', 'address' => '50 Jurong Gateway Road, #11-03', 'city' => 'Singapore'],
            ['name' => 'Siti Fatimah Binte Ali', 'email' => 'siti@woodlands.sg', 'phone' => '+65 8678 9012', 'company_name' => 'Woodlands Industrial Supply', 'tax_number' => '201589012F', 'address' => '2 Woodlands Exchange, #05-22', 'city' => 'Singapore'],
            ['name' => 'David Tan Boon Kiat', 'email' => 'bk.tan@sentosaprop.sg', 'phone' => '+65 9789 0123', 'company_name' => 'Sentosa Properties Pte Ltd', 'tax_number' => '201490123G', 'address' => '3 Gateway Drive, #24-01', 'city' => 'Singapore'],
            ['name' => 'Goh Li Ting', 'email' => 'lt.goh@bukitbatok.sg', 'phone' => '+65 8890 1234', 'company_name' => 'Bukit Batok F&B Group', 'tax_number' => '202101234H', 'address' => '1 Bukit Batok Crescent, #04-18', 'city' => 'Singapore'],
            ['name' => 'Muhammad Hafiz Bin Ismail', 'email' => 'hafiz@eunosauto.sg', 'phone' => '+65 9901 2345', 'company_name' => 'Eunos Auto Services Pte Ltd', 'tax_number' => '201312345J', 'address' => '371 Ubi Avenue 3, #01-05', 'city' => 'Singapore'],
            ['name' => 'Serene Koh Yu Ling', 'email' => 'yl.koh@pasirris.sg', 'phone' => '+65 8012 3456', 'company_name' => 'Pasir Ris Digital Agency', 'tax_number' => '202223456L', 'address' => '5 Elias Road, #03-10', 'city' => 'Singapore'],
        ];

        $customers = [];
        foreach ($customersData as $data) {
            $data['country'] = 'Singapore';
            $data['currency'] = 'SGD';
            $data['balance'] = 0.00;
            $data['is_active'] = true;
            $customers[] = Customer::create($data);
        }

        return $customers;
    }

    // --- SECTION: Vendors ---
    private function createVendors(): array
    {
        $vendorsData = [
            ['name' => 'CloudServe Technologies', 'email' => 'billing@cloudserve.example.com', 'phone' => '+65 6800 0199', 'company_name' => 'CloudServe Technologies Pte Ltd', 'tax_number' => '201500001E', 'address' => '23 Church Street, #10-01 Capital Square', 'city' => 'Singapore'],
            ['name' => 'CoWork Spaces SG', 'email' => 'accounts@coworkspaces.example.com', 'phone' => '+65 6567 8901', 'company_name' => 'CoWork Spaces SG Pte Ltd', 'tax_number' => '201900002D', 'address' => '71 Robinson Road, #03-00', 'city' => 'Singapore'],
            ['name' => 'TeleConnect Business', 'email' => 'billing@teleconnect.example.com', 'phone' => '+65 6820 8888', 'company_name' => 'TeleConnect Pte Ltd', 'tax_number' => '201800003H', 'address' => '67 Ubi Avenue 1', 'city' => 'Singapore'],
            ['name' => 'TechGear Supplies', 'email' => 'sales@techgear.example.com', 'phone' => '+65 6516 0888', 'company_name' => 'TechGear Supplies Pte Ltd', 'tax_number' => '201700004D', 'address' => '23 Serangoon Central, #04-01', 'city' => 'Singapore'],
            ['name' => 'ProAudit LLP', 'email' => 'tax@proaudit.example.com', 'phone' => '+65 6594 8688', 'company_name' => 'ProAudit LLP', 'tax_number' => '201600005L', 'address' => '8 Wilkie Road, #03-01', 'city' => 'Singapore'],
        ];

        $vendors = [];
        foreach ($vendorsData as $data) {
            $data['country'] = 'Singapore';
            $data['currency'] = 'SGD';
            $data['balance'] = 0.00;
            $data['is_active'] = true;
            $vendors[] = Vendor::create($data);
        }

        return $vendors;
    }

    // --- SECTION: Invoices ---
    private function createInvoices(array $customers, array $items): array
    {
        $statuses = [
            'draft', 'draft', 'draft',
            'sent', 'sent', 'sent', 'sent', 'sent',
            'partial', 'partial', 'partial',
            'paid', 'paid', 'paid', 'paid', 'paid', 'paid', 'paid', 'paid', 'paid',
            'overdue', 'overdue', 'overdue', 'overdue',
            'draft', 'sent', 'partial', 'paid', 'paid', 'overdue',
        ];

        $invoices = [];

        for ($i = 0; $i < 30; $i++) {
            $customer = $customers[$i % count($customers)];
            $status = $statuses[$i];
            $invoiceDate = now()->subDays(rand(5, 120));
            $dueDate = $invoiceDate->copy()->addDays(30);

            // Pick 1-3 random items for this invoice
            $itemCount = rand(1, 3);
            $selectedItems = [];
            $shuffledKeys = array_rand($items, min($itemCount, count($items)));
            if (!is_array($shuffledKeys)) {
                $shuffledKeys = [$shuffledKeys];
            }
            foreach ($shuffledKeys as $key) {
                $selectedItems[] = $items[$key];
            }

            $subtotal = 0;
            $taxTotal = 0;
            foreach ($selectedItems as $item) {
                $qty = rand(1, 5);
                $lineTotal = $item->sale_price * $qty;
                $lineTax = round($lineTotal * 0.09, 2);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            $total = round($subtotal + $taxTotal, 2);
            $paidAmount = 0;

            if ($status === 'paid') {
                $paidAmount = $total;
            } elseif ($status === 'partial') {
                $paidAmount = round($total * (rand(20, 60) / 100), 2);
            }

            $dueAmount = round($total - $paidAmount, 2);

            if ($status === 'overdue') {
                $dueDate = now()->subDays(rand(5, 45));
            }

            $invoice = Invoice::create([
                'invoice_number' => 'INV-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'notes' => 'Thank you for your business. Please quote the invoice number when making payment.',
                'terms' => 'Payment due within 30 days. Late payment subject to 1.5% monthly interest.',
                'public_token' => Str::random(32),
                'currency_code' => 'SGD',
                'exchange_rate' => 1.000000,
            ]);

            // Create invoice line items
            foreach ($selectedItems as $item) {
                $qty = rand(1, 5);
                $lineTotal = $item->sale_price * $qty;
                $lineTax = round($lineTotal * 0.09, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $qty,
                    'price' => $item->sale_price,
                    'tax_rate' => 9.00,
                    'tax_amount' => $lineTax,
                    'total' => round($lineTotal + $lineTax, 2),
                ]);
            }

            $invoices[] = $invoice;
        }

        // Update customer balances
        foreach ($customers as $customer) {
            $outstanding = Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
                ->sum('due_amount');
            $customer->update(['balance' => $outstanding]);
        }

        return $invoices;
    }

    // --- SECTION: Bills ---
    private function createBills(array $vendors, array $items): array
    {
        $statuses = ['draft', 'received', 'received', 'partial', 'paid', 'paid', 'paid', 'overdue', 'received', 'paid', 'draft', 'overdue', 'paid', 'received', 'partial'];
        $bills = [];

        for ($i = 0; $i < 15; $i++) {
            $vendor = $vendors[$i % count($vendors)];
            $status = $statuses[$i];
            $billDate = now()->subDays(rand(5, 90));
            $dueDate = $billDate->copy()->addDays(30);

            // Pick 1-2 random items
            $shuffledKeys = array_rand($items, min(rand(1, 2), count($items)));
            if (!is_array($shuffledKeys)) {
                $shuffledKeys = [$shuffledKeys];
            }

            $subtotal = 0;
            $taxTotal = 0;
            $lineItems = [];
            foreach ($shuffledKeys as $key) {
                $item = $items[$key];
                $qty = rand(1, 3);
                $lineTotal = $item->purchase_price > 0 ? $item->purchase_price * $qty : rand(500, 5000);
                $lineTax = round($lineTotal * 0.09, 2);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
                $lineItems[] = ['item' => $item, 'qty' => $qty, 'price' => $item->purchase_price > 0 ? $item->purchase_price : $lineTotal / $qty, 'tax' => $lineTax, 'total' => $lineTotal];
            }

            $total = round($subtotal + $taxTotal, 2);
            $paidAmount = $status === 'paid' ? $total : ($status === 'partial' ? round($total * 0.5, 2) : 0);
            $dueAmount = round($total - $paidAmount, 2);

            if ($status === 'overdue') {
                $dueDate = now()->subDays(rand(5, 30));
            }

            $bill = Bill::create([
                'bill_number' => 'BILL-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'vendor_id' => $vendor->id,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'notes' => null,
                'currency_code' => 'SGD',
                'exchange_rate' => 1.000000,
            ]);

            foreach ($lineItems as $li) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'item_id' => $li['item']->id,
                    'name' => $li['item']->name,
                    'description' => $li['item']->description,
                    'quantity' => $li['qty'],
                    'price' => $li['price'],
                    'tax_rate' => 9.00,
                    'tax_amount' => $li['tax'],
                    'total' => round($li['total'] + $li['tax'], 2),
                ]);
            }

            $bills[] = $bill;
        }

        // Update vendor balances
        foreach ($vendors as $vendor) {
            $outstanding = Bill::where('vendor_id', $vendor->id)
                ->whereIn('status', ['draft', 'received', 'partial', 'overdue'])
                ->sum('due_amount');
            $vendor->update(['balance' => $outstanding]);
        }

        return $bills;
    }

    // --- SECTION: Quotes ---
    private function createQuotes(array $customers, array $items): void
    {
        $statuses = ['draft', 'sent', 'accepted', 'declined', 'sent', 'draft', 'accepted', 'sent', 'draft', 'declined'];

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers[$i % count($customers)];
            $status = $statuses[$i];
            $quoteDate = now()->subDays(rand(3, 60));

            $shuffledKeys = array_rand($items, min(rand(1, 3), count($items)));
            if (!is_array($shuffledKeys)) {
                $shuffledKeys = [$shuffledKeys];
            }

            $subtotal = 0;
            $taxTotal = 0;
            $lineItems = [];
            foreach ($shuffledKeys as $key) {
                $item = $items[$key];
                $qty = rand(1, 5);
                $lineTotal = $item->sale_price * $qty;
                $lineTax = round($lineTotal * 0.09, 2);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
                $lineItems[] = ['item' => $item, 'qty' => $qty, 'tax' => $lineTax, 'total' => $lineTotal];
            }

            $total = round($subtotal + $taxTotal, 2);

            $quote = Quote::create([
                'quote_number' => 'QTN-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'quote_date' => $quoteDate,
                'expiry_date' => $quoteDate->copy()->addDays(30),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'status' => $status,
                'notes' => 'This quotation is valid for 30 days from the date of issue.',
                'terms' => 'Prices subject to change after the validity period.',
                'public_token' => Str::random(32),
                'currency_code' => 'SGD',
                'exchange_rate' => 1.000000,
            ]);

            foreach ($lineItems as $li) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'item_id' => $li['item']->id,
                    'name' => $li['item']->name,
                    'description' => $li['item']->description,
                    'quantity' => $li['qty'],
                    'price' => $li['item']->sale_price,
                    'tax_rate' => 9.00,
                    'tax_amount' => $li['tax'],
                    'total' => round($li['total'] + $li['tax'], 2),
                ]);
            }
        }
    }

    // --- SECTION: Transactions ---
    private function createTransactions(array $bankAccounts, array $customers, array $vendors, array $invoices, array $bills, array $categories): void
    {
        $bankIds = [$bankAccounts['dbs']->id, $bankAccounts['ocbc']->id, $bankAccounts['uob']->id];
        $paymentMethods = ['bank_transfer', 'paynow', 'card', 'cheque', 'cash'];
        $incomeCategories = ['Product Sales', 'Consulting Services', 'Subscription Revenue'];
        $expenseCategories = ['Cloud & Hosting', 'Office Rent', 'Utilities & Telecom', 'Marketing', 'Professional Fees'];

        // 12 income transactions linked to paid/partial invoices
        $paidInvoices = array_filter($invoices, fn($inv) => in_array($inv->status, ['paid', 'partial']));
        $paidInvoices = array_values($paidInvoices);

        for ($i = 0; $i < 12 && $i < count($paidInvoices); $i++) {
            $inv = $paidInvoices[$i];
            $catName = $incomeCategories[$i % count($incomeCategories)];

            Transaction::create([
                'type' => 'income',
                'bank_account_id' => $bankIds[$i % count($bankIds)],
                'customer_id' => $inv->customer_id,
                'invoice_id' => $inv->id,
                'category_id' => $categories[$catName] ?? null,
                'amount' => $inv->paid_amount,
                'payment_method' => $paymentMethods[$i % count($paymentMethods)],
                'reference_number' => 'TXN-INC-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'transaction_date' => $inv->invoice_date->copy()->addDays(rand(1, 15)),
                'description' => 'Payment received for ' . $inv->invoice_number,
            ]);
        }

        // 8 expense transactions
        $expenseAmounts = [450.00, 3200.00, 185.50, 1200.00, 600.00, 2800.00, 350.00, 890.00];
        $expenseDescs = [
            'Cloud hosting and storage services - September',
            'Office rent for September - CoWork Spaces Raffles Place',
            'Fibre broadband and mobile lines',
            'Digital advertising campaign - Q3 retargeting',
            'Tax filing services - Q3 GST',
            'Annual audit support 2025',
            'Server maintenance and patching - September',
            'ThinkPad T14s replacement unit',
        ];

        for ($i = 0; $i < 8; $i++) {
            $catName = $expenseCategories[$i % count($expenseCategories)];
            $vendor = $vendors[$i % count($vendors)];

            Transaction::create([
                'type' => 'expense',
                'bank_account_id' => $bankIds[$i % count($bankIds)],
                'vendor_id' => $vendor->id,
                'bill_id' => isset($bills[$i]) ? $bills[$i]->id : null,
                'category_id' => $categories[$catName] ?? null,
                'amount' => $expenseAmounts[$i],
                'payment_method' => $paymentMethods[$i % count($paymentMethods)],
                'reference_number' => 'TXN-EXP-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'transaction_date' => now()->subDays(rand(1, 60)),
                'description' => $expenseDescs[$i],
            ]);
        }
    }

    // --- SECTION: Credit Notes ---
    private function createCreditNotes(array $customers, array $invoices, array $items): void
    {
        $reasons = [
            'Overbilling correction - wrong quantity invoiced',
            'Service not delivered as per agreement',
            'Goodwill credit for project delay',
            'Product returned - damaged on arrival',
            'Pricing error correction',
        ];

        // Use paid invoices for credit notes
        $paidInvoices = array_values(array_filter($invoices, fn($inv) => $inv->status === 'paid'));

        for ($i = 0; $i < 5 && $i < count($paidInvoices); $i++) {
            $invoice = $paidInvoices[$i];
            $item = $items[$i % count($items)];
            $qty = 1;
            $lineTotal = $item->sale_price * $qty;
            $lineTax = round($lineTotal * 0.09, 2);
            $subtotal = $lineTotal;
            $taxTotal = $lineTax;
            $total = round($subtotal + $taxTotal, 2);

            $cn = CreditNote::create([
                'credit_note_number' => 'CN-2026-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'credit_note_date' => $invoice->invoice_date->copy()->addDays(rand(5, 20)),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'status' => $i < 3 ? 'applied' : 'draft',
                'reason' => $reasons[$i],
                'notes' => 'Credit note issued as per agreed resolution.',
            ]);

            CreditNoteItem::create([
                'credit_note_id' => $cn->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $qty,
                'price' => $item->sale_price,
                'tax_rate' => 9.00,
                'tax_amount' => $lineTax,
                'total' => $total,
            ]);
        }
    }

    // --- SECTION: Journal Entries ---
    private function createJournalEntries(User $admin): void
    {
        // Get or create some chart of accounts entries
        $accounts = [
            ['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '2100', 'name' => 'GST Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '4000', 'name' => 'Service Revenue', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_revenue'],
            ['code' => '6100', 'name' => 'Rent Expense', 'type' => 'expense', 'sub_type' => 'operating_expense'],
            ['code' => '6200', 'name' => 'Salaries Expense', 'type' => 'expense', 'sub_type' => 'operating_expense'],
        ];

        $accountIds = [];
        foreach ($accounts as $acc) {
            $existing = Account::where('code', $acc['code'])->first();
            if (!$existing) {
                $existing = Account::create(array_merge($acc, ['balance' => 0, 'is_system' => false, 'is_active' => true]));
            }
            $accountIds[$acc['code']] = $existing->id;
        }

        $entries = [
            [
                'entry_number' => 'JE-2026-001',
                'entry_date' => now()->subDays(45),
                'description' => 'Accrual for September office rent',
                'reference' => 'RENT-SEP-2026',
                'lines' => [
                    ['account_id' => $accountIds['6100'], 'debit' => 3200.00, 'credit' => 0, 'description' => 'September office rent expense'],
                    ['account_id' => $accountIds['2000'], 'debit' => 0, 'credit' => 3200.00, 'description' => 'Accrued rent payable to WeWork'],
                ],
            ],
            [
                'entry_number' => 'JE-2026-002',
                'entry_date' => now()->subDays(30),
                'description' => 'Revenue recognition for completed project',
                'reference' => 'PROJ-ACME-Q3',
                'lines' => [
                    ['account_id' => $accountIds['1200'], 'debit' => 5450.00, 'credit' => 0, 'description' => 'AR - Project completion billing'],
                    ['account_id' => $accountIds['4000'], 'debit' => 0, 'credit' => 5000.00, 'description' => 'Service revenue recognised'],
                    ['account_id' => $accountIds['2100'], 'debit' => 0, 'credit' => 450.00, 'description' => 'GST output tax at 9%'],
                ],
            ],
            [
                'entry_number' => 'JE-2026-003',
                'entry_date' => now()->subDays(15),
                'description' => 'Monthly salary accrual including CPF employer contribution',
                'reference' => 'SAL-SEP-2026',
                'lines' => [
                    ['account_id' => $accountIds['6200'], 'debit' => 18500.00, 'credit' => 0, 'description' => 'Salaries and CPF for September'],
                    ['account_id' => $accountIds['2000'], 'debit' => 0, 'credit' => 18500.00, 'description' => 'Accrued salaries payable'],
                ],
            ],
            [
                'entry_number' => 'JE-2026-004',
                'entry_date' => now()->subDays(7),
                'description' => 'Depreciation of IT equipment - Q3',
                'reference' => 'DEP-Q3-2026',
                'lines' => [
                    ['account_id' => $accountIds['5000'], 'debit' => 1250.00, 'credit' => 0, 'description' => 'Depreciation expense - IT equipment'],
                    ['account_id' => $accountIds['1000'], 'debit' => 0, 'credit' => 1250.00, 'description' => 'Accumulated depreciation adjustment'],
                ],
            ],
            [
                'entry_number' => 'JE-2026-005',
                'entry_date' => now()->subDays(3),
                'description' => 'GST set-off for Q3 2026 filing period',
                'reference' => 'GST-Q3-OFFSET',
                'lines' => [
                    ['account_id' => $accountIds['2100'], 'debit' => 2850.00, 'credit' => 0, 'description' => 'GST output tax collected'],
                    ['account_id' => $accountIds['1000'], 'debit' => 0, 'credit' => 2850.00, 'description' => 'Net GST remitted to IRAS'],
                ],
            ],
        ];

        foreach ($entries as $entryData) {
            $lines = $entryData['lines'];
            unset($entryData['lines']);

            $entry = JournalEntry::create(array_merge($entryData, [
                'created_by' => $admin->id,
                'is_posted' => true,
                'reference_type' => null,
                'reference_id' => null,
            ]));

            foreach ($lines as $line) {
                JournalEntryLine::create(array_merge($line, [
                    'journal_entry_id' => $entry->id,
                ]));
            }
        }
    }

    // --- SECTION: Expense Claims ---
    private function createExpenseClaims(array $users, array $categories): void
    {
        ExpenseClaim::create([
            'claim_number' => 'EXP-2026-001',
            'user_id' => $users['accountant']->id,
            'claim_date' => now()->subDays(14),
            'title' => 'Client lunch meeting at Marina Bay Sands',
            'description' => 'Business lunch with Marina Bay Holdings to discuss Q4 ERP renewal. 3 attendees.',
            'total_amount' => 285.00,
            'status' => 'pending',
            'category_id' => $categories['Professional Fees'] ?? null,
        ]);

        ExpenseClaim::create([
            'claim_number' => 'EXP-2026-002',
            'user_id' => $users['admin']->id,
            'claim_date' => now()->subDays(21),
            'title' => 'Grab transport - client site visits',
            'description' => 'Travel to Jurong Tech Solutions and Woodlands Industrial Supply for on-site consultation.',
            'total_amount' => 67.50,
            'status' => 'approved',
            'approved_by' => $users['admin']->id,
            'approved_at' => now()->subDays(18),
            'category_id' => $categories['Travel & Transport'] ?? null,
        ]);

        ExpenseClaim::create([
            'claim_number' => 'EXP-2026-003',
            'user_id' => $users['accountant']->id,
            'claim_date' => now()->subDays(35),
            'title' => 'Office supplies from Popular Bookstore',
            'description' => 'Printer paper, toner cartridges, filing cabinets, and stationery for the finance team.',
            'total_amount' => 432.80,
            'status' => 'paid',
            'approved_by' => $users['admin']->id,
            'approved_at' => now()->subDays(32),
            'category_id' => $categories['Office Rent'] ?? null,
        ]);
    }

    // --- SECTION: Time Entries ---
    private function createTimeEntries(array $users, array $customers): void
    {
        $projects = [
            ['project' => 'ERP Migration - Marina Bay', 'description' => 'Database schema design and migration scripting', 'rate' => 150.00],
            ['project' => 'ERP Migration - Marina Bay', 'description' => 'API integration testing and bug fixes', 'rate' => 150.00],
            ['project' => 'Mobile App - Orchard Retail', 'description' => 'React Native UI components for inventory module', 'rate' => 180.00],
            ['project' => 'Mobile App - Orchard Retail', 'description' => 'Push notification service integration', 'rate' => 180.00],
            ['project' => 'Cloud Hosting Setup', 'description' => 'Cloud infrastructure provisioning and Terraform configs', 'rate' => 200.00],
            ['project' => 'Security Audit - Jurong Tech', 'description' => 'Penetration testing and vulnerability assessment', 'rate' => 200.00],
            ['project' => 'Security Audit - Jurong Tech', 'description' => 'Security report documentation and remediation plan', 'rate' => 200.00],
            ['project' => 'Website Revamp - Sentosa', 'description' => 'Frontend redesign and CMS migration', 'rate' => 150.00],
            ['project' => 'Network Setup - Woodlands', 'description' => 'Office network cabling, switch and firewall configuration', 'rate' => 120.00],
            ['project' => 'Technical Support', 'description' => 'Tier-2 support tickets resolution and SLA monitoring', 'rate' => 120.00],
        ];

        $allUsers = [$users['admin'], $users['accountant']];

        for ($i = 0; $i < 10; $i++) {
            $user = $allUsers[$i % count($allUsers)];
            $customer = $customers[$i % count($customers)];
            $project = $projects[$i];
            $hours = round(rand(15, 80) / 10, 1); // 1.5 to 8.0 hours

            TimeEntry::create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'project' => $project['project'],
                'description' => $project['description'],
                'entry_date' => now()->subDays(rand(1, 45)),
                'hours' => $hours,
                'rate' => $project['rate'],
                'amount' => round($hours * $project['rate'], 2),
                'is_billable' => true,
                'is_invoiced' => $i < 4, // first 4 already invoiced
            ]);
        }
    }

    // --- SECTION: Budgets ---
    private function createBudgets(array $categories): void
    {
        $year = now()->year;

        $budgetsData = [
            ['category' => 'Cloud & Hosting', 'month' => now()->month, 'amount' => 1500.00, 'notes' => 'Cloud hosting monthly budget for production and staging'],
            ['category' => 'Marketing', 'month' => now()->month, 'amount' => 3000.00, 'notes' => 'Digital marketing spend including search and social ads'],
            ['category' => 'Office Rent', 'month' => now()->month, 'amount' => 4500.00, 'notes' => 'Co-working space, utilities, and shared facilities'],
        ];

        foreach ($budgetsData as $data) {
            $catId = $categories[$data['category']] ?? null;
            if (!$catId) {
                continue;
            }

            Budget::create([
                'category_id' => $catId,
                'year' => $year,
                'month' => $data['month'],
                'amount' => $data['amount'],
                'notes' => $data['notes'],
            ]);
        }
    }

    // --- SECTION: Recurring Templates ---
    private function createRecurringTemplates(array $customers, array $vendors, array $items): void
    {
        // Template 1: Monthly cloud hosting invoice
        $hostingItem = null;
        $supportItem = null;
        foreach ($items as $item) {
            if ($item->sku === 'SRV-HST-001') {
                $hostingItem = $item;
            }
            if ($item->sku === 'SRV-SUP-001') {
                $supportItem = $item;
            }
        }

        if ($hostingItem) {
            $subtotal = $hostingItem->sale_price;
            $taxTotal = round($subtotal * 0.09, 2);
            $total = round($subtotal + $taxTotal, 2);

            $template1 = RecurringTemplate::create([
                'type' => 'invoice',
                'customer_id' => $customers[0]->id,
                'vendor_id' => null,
                'frequency' => 'monthly',
                'next_due_date' => now()->addMonth()->startOfMonth(),
                'last_generated_at' => now()->startOfMonth(),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'notes' => 'Monthly cloud hosting and managed services.',
                'terms' => 'Payment due within 30 days via PayNow or bank transfer.',
                'is_active' => true,
            ]);

            RecurringTemplateItem::create([
                'recurring_template_id' => $template1->id,
                'item_id' => $hostingItem->id,
                'name' => $hostingItem->name,
                'description' => $hostingItem->description,
                'quantity' => 1,
                'price' => $hostingItem->sale_price,
                'tax_rate' => 9.00,
                'tax_amount' => $taxTotal,
                'total' => $total,
            ]);
        }

        if ($supportItem) {
            $subtotal = $supportItem->sale_price;
            $taxTotal = round($subtotal * 0.09, 2);
            $total = round($subtotal + $taxTotal, 2);

            $template2 = RecurringTemplate::create([
                'type' => 'invoice',
                'customer_id' => $customers[4]->id,
                'vendor_id' => null,
                'frequency' => 'monthly',
                'next_due_date' => now()->addMonth()->startOfMonth(),
                'last_generated_at' => now()->startOfMonth(),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'total' => $total,
                'notes' => 'Monthly priority technical support plan.',
                'terms' => 'Net 30. Support SLA: 4-hour response for critical issues.',
                'is_active' => true,
            ]);

            RecurringTemplateItem::create([
                'recurring_template_id' => $template2->id,
                'item_id' => $supportItem->id,
                'name' => $supportItem->name,
                'description' => $supportItem->description,
                'quantity' => 1,
                'price' => $supportItem->sale_price,
                'tax_rate' => 9.00,
                'tax_amount' => $taxTotal,
                'total' => $total,
            ]);
        }
    }
}
