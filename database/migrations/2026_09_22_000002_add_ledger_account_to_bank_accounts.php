<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cash-in-ledger Stage B1: link each operational bank/cash account to its own
 * chart-of-accounts ledger account, so every cash movement can be posted to the
 * double-entry ledger and the Balance Sheet can read cash from the ledger
 * (tying to the Banking page) instead of summing current_balance directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('current_balance');
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
