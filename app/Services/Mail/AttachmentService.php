<?php

namespace App\Services\Mail;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function download(Attachment $attachment, ImapClient $client): Attachment
    {
        if ($attachment->is_downloaded
            && $attachment->storage_path
            && Storage::disk('attachments')->exists($attachment->storage_path)) {
            return $attachment;
        }

        $message = $attachment->message;
        $folder = $message->folder;

        $data = $client->downloadAttachment(
            $folder->imap_name,
            (int) $message->imap_uid,
            $attachment->imap_part_number
        );

        $safeFilename = $this->sanitizeFilename($data['filename']);
        $storagePath = sprintf(
            '%d/%d/%s',
            $message->email_account_id,
            $message->id,
            $safeFilename
        );

        Storage::disk('attachments')->put($storagePath, $data['content']);

        $attachment->update([
            'storage_path' => $storagePath,
            'is_downloaded' => true,
            'mime_type' => $data['mime_type'],
            'size' => $data['size'],
        ]);

        return $attachment->fresh();
    }

    public function getStoragePath(Attachment $attachment): string
    {
        if (! $attachment->is_downloaded || ! $attachment->storage_path) {
            throw new \RuntimeException('Attachment not downloaded yet');
        }

        return Storage::disk('attachments')->path($attachment->storage_path);
    }

    private function sanitizeFilename(string $filename): string
    {
        $safe = basename($filename);
        $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $safe);
        $safe = ltrim($safe, '.');

        if (empty($safe)) {
            $safe = 'attachment';
        }

        return Str::uuid().'_'.$safe;
    }
}
