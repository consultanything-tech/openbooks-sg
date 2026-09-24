<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'account_number', 'bank_name',
        'ifsc_code', 'branch_name', 'account_type', 'upi_id', 'bank_address',
        'currency', 'opening_balance', 'current_balance', 'account_id', 'is_default', 'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function getAccountNameAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    public function setAccountNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** The chart-of-accounts ledger account this bank/cash account posts to. */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
