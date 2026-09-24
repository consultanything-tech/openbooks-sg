<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\CurrencyRate;
use App\Models\Tax;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    use LogsActivity;

    /**
     * Ensure any missing columns exist on the companies table automatically.
     */
    private function ensureCompanyTableSchema(): void
    {
        try {
            if (Schema::hasTable('companies')) {
                Schema::table('companies', function (Blueprint $table) {
                    if (! Schema::hasColumn('companies', 'state')) {
                        $table->string('state')->nullable()->after('city');
                    }
                    if (! Schema::hasColumn('companies', 'financial_year')) {
                        $table->string('financial_year')->default('January - December')->after('currency_symbol');
                    }
                    if (! Schema::hasColumn('companies', 'financial_year_start')) {
                        $table->string('financial_year_start')->default('01-01')->after('financial_year');
                    }
                    if (! Schema::hasColumn('companies', 'nvidia_api_key')) {
                        $table->text('nvidia_api_key')->nullable()->after('tax_number');
                    }
                    if (! Schema::hasColumn('companies', 'nvidia_model')) {
                        $table->string('nvidia_model')->default('meta/llama-3.2-11b-vision-instruct')->after('nvidia_api_key');
                    }
                    if (! Schema::hasColumn('companies', 'invoice_prefix')) {
                        $table->string('invoice_prefix')->default('INV')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'bill_prefix')) {
                        $table->string('bill_prefix')->default('BILL')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'credit_note_prefix')) {
                        $table->string('credit_note_prefix')->default('CN')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'default_payment_terms')) {
                        $table->text('default_payment_terms')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'default_payment_notes')) {
                        $table->text('default_payment_notes')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'invoice_footer')) {
                        $table->text('invoice_footer')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'accent_color')) {
                        $table->string('accent_color')->default('#4f46e5')->nullable();
                    }
                    if (! Schema::hasColumn('companies', 'show_logo_on_documents')) {
                        $table->boolean('show_logo_on_documents')->default(true);
                    }
                    if (! Schema::hasColumn('companies', 'show_tax_number_on_documents')) {
                        $table->boolean('show_tax_number_on_documents')->default(true);
                    }
                    if (! Schema::hasColumn('companies', 'show_phone_on_documents')) {
                        $table->boolean('show_phone_on_documents')->default(true);
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Auto-heal companies table columns warning: '.$e->getMessage());
        }
    }

    public function index()
    {
        $this->ensureCompanyTableSchema();

        $company = Company::first() ?? new Company([
            'currency_code' => 'SGD',
            'currency_symbol' => 'S$',
            'financial_year' => 'January - December',
            'financial_year_start' => '01-01',
        ]);
        $categories = Category::orderBy('type')->orderBy('name')->get();
        $taxes = Tax::orderBy('name')->get();

        return view('settings.index', compact('company', 'categories', 'taxes'));
    }

    public function updateCompany(Request $request)
    {
        $this->ensureCompanyTableSchema();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'currency_code' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'required|string|max:10',
        ];

        if ($request->hasFile('logo')) {
            $rules['logo'] = 'image|mimes:jpg,jpeg,png,svg|max:2048';
        }

        $request->validate($rules);

        try {
            $company = Company::first();
            if (! $company) {
                $company = new Company;
            }

            $currencyCode = $request->currency_code ?: ($request->currency ?: 'SGD');

            $data = $request->only([
                'name', 'email', 'phone', 'address', 'city', 'state', 'country',
                'tax_number', 'currency_symbol', 'financial_year_start', 'financial_year',
                'invoice_prefix', 'bill_prefix', 'credit_note_prefix',
                'default_payment_terms', 'default_payment_notes', 'invoice_footer',
                'accent_color',
            ]);

            // Boolean fields — checkboxes are absent when unchecked, so use $request->boolean()
            $data['show_logo_on_documents'] = $request->boolean('show_logo_on_documents', true);
            $data['show_tax_number_on_documents'] = $request->boolean('show_tax_number_on_documents', true);
            $data['show_phone_on_documents'] = $request->boolean('show_phone_on_documents', true);

            // Filter data by columns that exist in the database table to prevent SQL 1054 crashes
            $columns = Schema::hasTable('companies') ? Schema::getColumnListing('companies') : [];
            if (! empty($columns)) {
                $filteredData = array_intersect_key($data, array_flip($columns));
            } else {
                $filteredData = $data;
            }

            $company->fill($filteredData);

            if (empty($columns) || in_array('currency_code', $columns)) {
                $company->currency_code = $currencyCode;
            }

            if (empty($columns) || in_array('financial_year', $columns)) {
                if ($request->filled('financial_year')) {
                    $company->financial_year = $request->financial_year;
                    if (empty($columns) || in_array('financial_year_start', $columns)) {
                        if ($request->financial_year === 'April - March') {
                            $company->financial_year_start = '04-01';
                        } elseif ($request->financial_year === 'January - December') {
                            $company->financial_year_start = '01-01';
                        }
                    }
                }
            }

            // Handle logo upload
            if ($request->hasFile('logo') && (empty($columns) || in_array('logo_path', $columns))) {
                // Delete old logo if replacing
                if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $path = $request->file('logo')->store('logos', 'public');
                $company->logo_path = $path;
            }

            $company->save();
            $this->logActivity('updated', 'Updated company settings', 'Company', $company->id);

            return back()->with('success', 'Company and financial settings updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Settings update error: '.$e->getMessage());

            return back()->with('error', 'Could not save settings: '.$e->getMessage());
        }
    }

    public function updateBranding(Request $request)
    {
        $this->ensureCompanyTableSchema();

        $rules = [
            'invoice_prefix' => 'nullable|string|max:20',
            'bill_prefix' => 'nullable|string|max:20',
            'credit_note_prefix' => 'nullable|string|max:20',
            'default_payment_terms' => 'nullable|string|max:2000',
            'default_payment_notes' => 'nullable|string|max:2000',
            'invoice_footer' => 'nullable|string|max:2000',
            'accent_color' => 'nullable|string|max:20',
        ];

        if ($request->hasFile('logo')) {
            $rules['logo'] = 'image|mimes:jpg,jpeg,png,svg|max:2048';
        }

        $request->validate($rules);

        try {
            $company = Company::first();
            if (! $company) {
                $company = new Company;
            }

            $data = $request->only([
                'invoice_prefix', 'bill_prefix', 'credit_note_prefix',
                'default_payment_terms', 'default_payment_notes', 'invoice_footer',
                'accent_color', 'paynow_id', 'paynow_id_type', 'paynow_name',
            ]);

            $data['show_logo_on_documents'] = $request->boolean('show_logo_on_documents', false);
            $data['show_tax_number_on_documents'] = $request->boolean('show_tax_number_on_documents', false);
            $data['show_phone_on_documents'] = $request->boolean('show_phone_on_documents', false);

            $columns = Schema::hasTable('companies') ? Schema::getColumnListing('companies') : [];
            if (! empty($columns)) {
                $filteredData = array_intersect_key($data, array_flip($columns));
            } else {
                $filteredData = $data;
            }

            $company->fill($filteredData);

            // Handle logo upload
            if ($request->hasFile('logo') && (empty($columns) || in_array('logo_path', $columns))) {
                if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $path = $request->file('logo')->store('logos', 'public');
                $company->logo_path = $path;
            }

            $company->save();
            $this->logActivity('updated', 'Updated branding & document settings', 'Company', $company->id);

            return back()->with('success', 'Branding & document settings updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Branding settings update error: '.$e->getMessage());

            return back()->with('error', 'Could not save branding settings: '.$e->getMessage());
        }
    }

    public function updateAi(Request $request)
    {
        $this->ensureCompanyTableSchema();

        $request->validate([
            'nvidia_api_key' => 'nullable|string',
            'nvidia_model' => 'nullable|string|max:100',
        ]);

        try {
            $company = Company::first();
            if (! $company) {
                $company = new Company;
            }

            $columns = Schema::hasTable('companies') ? Schema::getColumnListing('companies') : [];
            if (empty($columns) || in_array('nvidia_api_key', $columns)) {
                $company->nvidia_api_key = trim($request->input('nvidia_api_key', ''));
            }
            if (empty($columns) || in_array('nvidia_model', $columns)) {
                $company->nvidia_model = $request->input('nvidia_model') ?: 'meta/llama-3.2-11b-vision-instruct';
            }
            $company->save();

            return back()->with('success', 'AI Assistant & NVIDIA NIM settings updated successfully.');
        } catch (\Throwable $e) {
            Log::error('AI settings update error: '.$e->getMessage());

            return back()->with('error', 'Could not save AI settings: '.$e->getMessage());
        }
    }

    public function removeLogo()
    {
        try {
            $company = Company::first();
            if ($company && $company->logo_path) {
                if (Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $company->logo_path = null;
                $company->save();
            }

            return back()->with('success', 'Company logo removed successfully.');
        } catch (\Throwable $e) {
            Log::error('Logo removal error: '.$e->getMessage());

            return back()->with('error', 'Could not remove logo: '.$e->getMessage());
        }
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense,item,other',
            'color' => 'nullable|string|max:20',
        ]);

        try {
            Category::create([
                'name' => $request->name,
                'type' => in_array($request->type, ['income', 'expense', 'item']) ? $request->type : 'expense',
                'color' => $request->color ?? '#10b981',
                'is_active' => true,
            ]);

            return back()->with('success', 'Category added successfully.');
        } catch (\Throwable $e) {
            Log::error('Category add error: '.$e->getMessage());

            return back()->with('error', 'Could not add category: '.$e->getMessage());
        }
    }

    public function storeTax(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'nullable|string|max:20',
        ]);

        try {
            Tax::create([
                'name' => $request->name,
                'rate' => $request->rate,
                'type' => in_array($request->type, ['normal', 'inclusive', 'compound']) ? $request->type : 'normal',
                'is_active' => true,
            ]);

            return back()->with('success', 'Tax rate added successfully.');
        } catch (\Throwable $e) {
            Log::error('Tax rate add error: '.$e->getMessage());

            return back()->with('error', 'Could not add tax rate: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | User Management (Admin Only)
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        $users = User::orderByRaw("CASE role WHEN 'ADMIN' THEN 1 WHEN 'ACCOUNTANT' THEN 2 WHEN 'VIEWER' THEN 3 ELSE 4 END")->orderBy('name')->get();

        return view('settings.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,ACCOUNTANT,VIEWER',
        ]);

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true,
                'created_by_user_id' => Auth::id(),
            ]);

            return back()->with('success', 'User created successfully.');
        } catch (\Throwable $e) {
            Log::error('User creation error: '.$e->getMessage());

            return back()->with('error', 'Could not create user: '.$e->getMessage());
        }
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:ADMIN,ACCOUNTANT,VIEWER',
            'is_active' => 'required|boolean',
        ]);

        try {
            $user = User::findOrFail($id);

            // Prevent admin from deactivating or demoting themselves
            if ((int) $id === (int) Auth::id()) {
                if (! $request->is_active) {
                    return back()->with('error', 'You cannot deactivate your own account.');
                }
                if (strtoupper($request->role) !== 'ADMIN') {
                    return back()->with('error', 'You cannot change your own role from Admin.');
                }
            }

            $user->role = $request->role;
            $user->is_active = $request->is_active;
            $user->save();

            return back()->with('success', 'User updated successfully.');
        } catch (\Throwable $e) {
            Log::error('User update error: '.$e->getMessage());

            return back()->with('error', 'Could not update user: '.$e->getMessage());
        }
    }

    public function destroyUser($id)
    {
        try {
            if ((int) $id === (int) Auth::id()) {
                return back()->with('error', 'You cannot delete your own account.');
            }

            $user = User::findOrFail($id);
            $user->delete();

            return back()->with('success', 'User deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('User deletion error: '.$e->getMessage());

            return back()->with('error', 'Could not delete user: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Management
    |--------------------------------------------------------------------------
    */

    public function currencies()
    {
        $company = Company::first() ?? new Company(['currency_code' => 'SGD', 'currency_symbol' => 'S$']);
        $currencies = CurrencyRate::orderBy('currency_code')->get();

        return view('settings.currencies', compact('company', 'currencies'));
    }

    public function storeCurrency(Request $request)
    {
        $request->validate([
            'currency_code' => 'required|string|max:10|unique:currency_rates,currency_code',
            'currency_name' => 'required|string|max:100',
            'currency_symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.000001',
        ]);

        try {
            CurrencyRate::create([
                'currency_code' => strtoupper($request->currency_code),
                'currency_name' => $request->currency_name,
                'currency_symbol' => $request->currency_symbol,
                'exchange_rate' => $request->exchange_rate,
                'is_active' => true,
                'updated_at' => now(),
            ]);

            $this->logActivity('created', "Added currency {$request->currency_code}", 'CurrencyRate', null);

            return back()->with('success', 'Currency added successfully.');
        } catch (\Throwable $e) {
            Log::error('Currency add error: '.$e->getMessage());

            return back()->with('error', 'Could not add currency: '.$e->getMessage());
        }
    }

    public function updateCurrency(Request $request, $id)
    {
        $request->validate([
            'exchange_rate' => 'required|numeric|min:0.000001',
            'currency_name' => 'nullable|string|max:100',
            'currency_symbol' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $currency = CurrencyRate::findOrFail($id);

            $currency->exchange_rate = $request->exchange_rate;
            if ($request->filled('currency_name')) {
                $currency->currency_name = $request->currency_name;
            }
            if ($request->filled('currency_symbol')) {
                $currency->currency_symbol = $request->currency_symbol;
            }
            $currency->is_active = $request->boolean('is_active', $currency->is_active);
            $currency->updated_at = now();
            $currency->save();

            $this->logActivity('updated', "Updated currency {$currency->currency_code} rate to {$currency->exchange_rate}", 'CurrencyRate', $currency->id);

            return back()->with('success', 'Currency rate updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Currency update error: '.$e->getMessage());

            return back()->with('error', 'Could not update currency: '.$e->getMessage());
        }
    }

    public function destroyCurrency($id)
    {
        try {
            $currency = CurrencyRate::findOrFail($id);
            $code = $currency->currency_code;
            $currency->delete();

            $this->logActivity('deleted', "Deleted currency {$code}", 'CurrencyRate', null);

            return back()->with('success', 'Currency removed successfully.');
        } catch (\Throwable $e) {
            Log::error('Currency deletion error: '.$e->getMessage());

            return back()->with('error', 'Could not delete currency: '.$e->getMessage());
        }
    }
}
