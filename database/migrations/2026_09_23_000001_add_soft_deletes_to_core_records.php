<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Trash instead of hard-delete for core records so the UI can offer "Undo".
| Every listed table already scopes queries through Eloquent, which excludes
| soft-deleted rows automatically once the model uses SoftDeletes.
*/
return new class extends Migration
{
    private array $tables = [
        'invoices',
        'quotes',
        'bills',
        'credit_notes',
        'customers',
        'vendors',
        'items',
        'expense_claims',
        'recurring_templates',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes()->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
