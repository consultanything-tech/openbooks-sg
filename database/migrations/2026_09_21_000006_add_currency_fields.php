<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add currency fields to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency_code', 10)->default('SGD')->after('notes');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000)->after('currency_code');
        });

        // Add currency fields to bills table
        Schema::table('bills', function (Blueprint $table) {
            $table->string('currency_code', 10)->default('SGD')->after('notes');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000)->after('currency_code');
        });

        // Create currency_rates table
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 10)->unique();
            $table->string('currency_name');
            $table->string('currency_symbol', 10);
            $table->decimal('exchange_rate', 10, 6);
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable();
        });

        // Seed default currencies
        DB::table('currency_rates')->insert([
            ['currency_code' => 'USD', 'currency_name' => 'US Dollar', 'currency_symbol' => '$', 'exchange_rate' => 1.350000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => "\u{20AC}", 'exchange_rate' => 1.460000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'GBP', 'currency_name' => 'British Pound', 'currency_symbol' => "\u{00A3}", 'exchange_rate' => 1.700000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'MYR', 'currency_name' => 'Malaysian Ringgit', 'currency_symbol' => 'RM', 'exchange_rate' => 0.310000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'JPY', 'currency_name' => 'Japanese Yen', 'currency_symbol' => "\u{00A5}", 'exchange_rate' => 0.009200, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'AUD', 'currency_name' => 'Australian Dollar', 'currency_symbol' => 'A$', 'exchange_rate' => 0.880000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'INR', 'currency_name' => 'Indian Rupee', 'currency_symbol' => "\u{20B9}", 'exchange_rate' => 0.016200, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'CNY', 'currency_name' => 'Chinese Yuan', 'currency_symbol' => "\u{00A5}", 'exchange_rate' => 0.186000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'HKD', 'currency_name' => 'Hong Kong Dollar', 'currency_symbol' => 'HK$', 'exchange_rate' => 0.173000, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'THB', 'currency_name' => 'Thai Baht', 'currency_symbol' => "\u{0E3F}", 'exchange_rate' => 0.039500, 'is_active' => true, 'updated_at' => now()],
            ['currency_code' => 'IDR', 'currency_name' => 'Indonesian Rupiah', 'currency_symbol' => 'Rp', 'exchange_rate' => 0.000087, 'is_active' => true, 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate']);
        });
    }
};
