<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $hidden = [
        'storage_path',
    ];

    protected $fillable = [
        'message_id',
        'filename',
        'mime_type',
        'size',
        'imap_part_number',
        'storage_path',
        'is_downloaded',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_downloaded' => 'boolean',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
