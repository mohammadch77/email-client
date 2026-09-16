<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'subject',
        'last_message_at',
        'message_count',
        'has_unread',
        'is_starred',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'has_unread' => 'boolean',
            'is_starred' => 'boolean',
            'message_count' => 'integer',
        ];
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
