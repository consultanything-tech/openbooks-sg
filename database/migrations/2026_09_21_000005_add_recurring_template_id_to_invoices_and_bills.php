<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('recurring_template_id')->nullable()->after('id')->constrained('recurring_templates')->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('recurring_template_id')->nullable()->after('id')->constrained('recurring_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['recurring_template_id']);
            $table->dropColumn('recurring_template_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['recurring_template_id']);
            $table->dropColumn('recurring_template_id');
        });
    }
};
