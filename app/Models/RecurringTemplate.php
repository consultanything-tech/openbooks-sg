<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type', 'customer_id', 'vendor_id', 'frequency',
        'next_due_date', 'last_generated_at',
        'subtotal', 'tax_total', 'discount_total', 'total',
        'notes', 'terms', 'is_active',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'last_generated_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return HasMany<RecurringTemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RecurringTemplateItem::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_template_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'recurring_template_id');
    }

    public function getRecipientNameAttribute(): string
    {
        if ($this->type === 'invoice') {
            return $this->customer->name ?? 'N/A';
        }

        return $this->vendor->name ?? 'N/A';
    }
}
