<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_prefix')->default('INV')->after('logo_path');
            $table->string('bill_prefix')->default('BILL')->after('invoice_prefix');
            $table->string('credit_note_prefix')->default('CN')->after('bill_prefix');
            $table->text('default_payment_terms')->nullable()->after('credit_note_prefix');
            $table->text('default_payment_notes')->nullable()->after('default_payment_terms');
            $table->text('invoice_footer')->nullable()->after('default_payment_notes');
            $table->string('accent_color')->default('#4f46e5')->after('invoice_footer');
            $table->boolean('show_logo_on_documents')->default(true)->after('accent_color');
            $table->boolean('show_tax_number_on_documents')->default(true)->after('show_logo_on_documents');
            $table->boolean('show_phone_on_documents')->default(true)->after('show_tax_number_on_documents');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_prefix',
                'bill_prefix',
                'credit_note_prefix',
                'default_payment_terms',
                'default_payment_notes',
                'invoice_footer',
                'accent_color',
                'show_logo_on_documents',
                'show_tax_number_on_documents',
                'show_phone_on_documents',
            ]);
        });
    }
};
