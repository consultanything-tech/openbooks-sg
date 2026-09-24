<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'currency_code', 'currency_name', 'currency_symbol',
        'exchange_rate', 'is_active', 'updated_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_active' => 'boolean',
        'updated_at' => 'datetime',
    ];
}
