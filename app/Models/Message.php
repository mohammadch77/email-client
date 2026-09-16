<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'folder_id',
        'thread_id',
        'imap_uid',
        'message_id_header',
        'in_reply_to',
        'references',
        'from_email',
        'from_name',
        'subject',
        'body_text',
        'body_html',
        'status',
        'is_read',
        'is_starred',
        'has_attachments',
        'sent_at',
        'received_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
            'has_attachments' => 'boolean',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
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

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
