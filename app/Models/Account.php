<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float $total_debit Aggregated debit alias on ledger queries
 * @property float $total_credit Aggregated credit alias on ledger queries
 */
class Account extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'sub_type', 'description',
        'parent_id', 'balance', 'is_system', 'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function recalculateBalance(): void
    {
        $debits = $this->journalLines()->sum('debit');
        $credits = $this->journalLines()->sum('credit');

        // Asset & Expense: debit-normal. Liability, Equity, Revenue: credit-normal.
        if (in_array($this->type, ['asset', 'expense'])) {
            $this->balance = $debits - $credits;
        } else {
            $this->balance = $credits - $debits;
        }
        $this->save();
    }
}
