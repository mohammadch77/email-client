<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'name',
        'type',
        'imap_name',
        'parent_id',
        'unread_count',
        'total_count',
    ];

    protected function casts(): array
    {
        return [
            'unread_count' => 'integer',
            'total_count' => 'integer',
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

    public function syncState(): HasOne
    {
        return $this->hasOne(SyncState::class);
    }
}
