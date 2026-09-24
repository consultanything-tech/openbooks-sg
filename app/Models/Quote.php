<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'quote_number', 'customer_id', 'quote_date', 'expiry_date',
        'subtotal', 'tax_total', 'discount_total', 'total',
        'status', 'notes', 'terms', 'public_token',
        'currency_code', 'exchange_rate', 'converted_invoice_id',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }
}
