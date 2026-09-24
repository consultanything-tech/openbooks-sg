<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'link', 'icon',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function send(int $userId, string $type, string $title, ?string $message = null, ?string $link = null, ?string $icon = null): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon ?? 'bell',
        ]);
    }

    public static function sendToAdmins(string $type, string $title, ?string $message = null, ?string $link = null, ?string $icon = null): void
    {
        $admins = User::whereIn('role', ['ADMIN', 'ACCOUNTANT'])->where('is_active', true)->get();
        foreach ($admins as $admin) {
            static::send($admin->id, $type, $title, $message, $link, $icon);
        }
    }
}
