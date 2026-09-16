<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncState extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'folder_id',
        'last_uid',
        'uid_validity',
        'full_sync_done',
        'synced_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'full_sync_done' => 'boolean',
            'synced_at' => 'datetime',
            'last_uid' => 'integer',
            'uid_validity' => 'integer',
        ];
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
