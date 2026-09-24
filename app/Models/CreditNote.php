<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'credit_note_number', 'invoice_id', 'customer_id',
        'credit_note_date', 'subtotal', 'tax_total', 'total',
        'status', 'reason', 'notes',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<CreditNoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
