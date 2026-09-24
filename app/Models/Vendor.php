<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'company_name', 'tax_number',
        'address', 'city', 'country', 'currency', 'balance', 'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Outstanding / Payable balance accessor
     */
    public function getOutstandingBalanceAttribute()
    {
        $dueBills = (float) $this->bills()->whereIn('status', ['draft', 'received', 'partial', 'overdue'])->sum('due_amount');
        if ($dueBills > 0) {
            return $dueBills;
        }

        return (float) ($this->balance ?? 0);
    }
}
