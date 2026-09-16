<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'display_name',
        'username',
        'password',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'status',
        'last_synced_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
        ];
    }

    public function getAttribute($key)
    {
        if ($key === 'password') {
            $value = $this->attributes['password'] ?? null;

            if ($value === null) {
                return null;
            }

            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $e) {
                return null;
            }
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'password' && $value !== null) {
            $value = Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function syncStates(): HasMany
    {
        return $this->hasMany(SyncState::class);
    }

    public function getImapConfig(): array
    {
        return [
            'host' => $this->imap_host,
            'port' => $this->imap_port,
            'encryption' => $this->imap_encryption,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'encryption' => $this->smtp_encryption,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function markAsError(): void
    {
        $this->status = 'error';
        $this->save();
    }

    public function markAsActive(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
