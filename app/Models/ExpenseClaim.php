<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseClaim extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'claim_number', 'user_id', 'claim_date', 'title', 'description',
        'total_amount', 'status', 'approved_by', 'approved_at',
        'rejection_reason', 'category_id', 'receipt_path',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'approved_at' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
