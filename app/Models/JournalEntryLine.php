<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aliases below exist only on ledger/report queries that join or aggregate
 * (selectRaw sums as c/d, joined account and journal entry columns).
 *
 * @property float $c SUM(credit) alias
 * @property float $d SUM(debit) alias
 * @property string $code Joined account code
 * @property string $name Joined account name
 * @property string $type Joined account type
 * @property string $entry_date Joined journal entry date
 * @property string $entry_number Joined journal entry number
 * @property string $entry_description Joined journal entry description
 * @property string $line_description Line description alias
 * @property string $reference Joined journal entry reference
 */
class JournalEntryLine extends Model
{
    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit', 'description',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
