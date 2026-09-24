<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_accounts', 'ifsc_code')) {
                $table->string('ifsc_code', 20)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('bank_accounts', 'branch_name')) {
                $table->string('branch_name', 100)->nullable()->after('ifsc_code');
            }
            if (! Schema::hasColumn('bank_accounts', 'account_type')) {
                $table->string('account_type', 50)->nullable()->after('branch_name');
            }
            if (! Schema::hasColumn('bank_accounts', 'upi_id')) {
                $table->string('upi_id', 100)->nullable()->after('account_type');
            }
            if (! Schema::hasColumn('bank_accounts', 'bank_address')) {
                $table->string('bank_address')->nullable()->after('upi_id');
            }
            if (! Schema::hasColumn('bank_accounts', 'status')) {
                $table->string('status', 20)->default('active')->after('is_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['ifsc_code', 'branch_name', 'account_type', 'upi_id', 'bank_address', 'status']);
        });
    }
};
