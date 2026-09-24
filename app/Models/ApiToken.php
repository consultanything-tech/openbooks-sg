<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id', 'token', 'name', 'last_used_at', 'expires_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new API token. Returns the plaintext token (shown once),
     * while the hashed version is stored in the database.
     */
    public static function generateFor(User $user, string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainToken = 'obk_'.Str::random(48);

        $token = static::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'name' => $name,
            'expires_at' => $expiresAt,
        ]);

        return ['model' => $token, 'plain_text_token' => $plainToken];
    }

    /**
     * Find a token by its plaintext value.
     */
    public static function findByPlainToken(string $plainToken): ?static
    {
        return static::where('token', hash('sha256', $plainToken))->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
