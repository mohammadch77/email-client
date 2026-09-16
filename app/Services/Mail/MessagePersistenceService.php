<?php

namespace App\Services\Mail;

use App\Models\Attachment;
use App\Models\EmailAccount;
use App\Models\Folder;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;

class MessagePersistenceService
{
    public function persist(
        EmailAccount $account,
        Folder $folder,
        array $rawMessage
    ): Message {
        return DB::transaction(function () use ($account, $folder, $rawMessage) {

            $thread = $this->resolveThread($account, $rawMessage);

            $message = Message::updateOrCreate(
                [
                    'email_account_id' => $account->id,
                    'folder_id' => $folder->id,
                    'imap_uid' => (string) $rawMessage['uid'],
                ],
                [
                    'thread_id' => $thread->id,
                    'message_id_header' => $rawMessage['message_id'] ?? null,
                    'in_reply_to' => $rawMessage['in_reply_to'] ?? null,
                    'references' => $rawMessage['references'] ?? null,
                    'from_email' => $rawMessage['from_email'],
                    'from_name' => $rawMessage['from_name'] ?? null,
                    'subject' => $rawMessage['subject'] ?? null,
                    'body_text' => $rawMessage['body_text'] ?? null,
                    'body_html' => $rawMessage['body_html'] ?? null,
                    'status' => 'received',
                    'is_read' => $rawMessage['is_read'] ?? false,
                    'has_attachments' => ! empty($rawMessage['attachments']),
                    'received_at' => $rawMessage['date'] ?? now(),
                ]
            );

            if ($message->wasRecentlyCreated) {
                $this->syncRecipients($message, $rawMessage);
                $this->syncAttachmentMetadata($message, $rawMessage);
            }

            $this->updateThreadStats($thread);

            return $message;
        });
    }

    private function resolveThread(
        EmailAccount $account,
        array $raw
    ): Thread {
        $inReplyTo = $raw['in_reply_to'] ?? null;
        $references = $raw['references'] ?? null;
        $subject = $this->normalizeSubject($raw['subject'] ?? '');

        if ($inReplyTo) {
            $parent = Message::where('email_account_id', $account->id)
                ->where('message_id_header', $inReplyTo)
                ->first();
            if ($parent && $parent->thread_id) {
                return $parent->thread;
            }
        }

        if ($references) {
            $refIds = array_filter(explode(' ', $references));
            foreach (array_reverse($refIds) as $refId) {
                $ref = Message::where('email_account_id', $account->id)
                    ->where('message_id_header', trim($refId))
                    ->first();
                if ($ref && $ref->thread_id) {
                    return $ref->thread;
                }
            }
        }

        return Thread::create([
            'email_account_id' => $account->id,
            'subject' => $subject,
            'last_message_at' => $raw['date'] ?? now(),
        ]);
    }

    private function normalizeSubject(string $subject): string
    {
        return trim(preg_replace(
            '/^(re|fwd?|aw|sv|wg):\s*/i',
            '',
            $subject
        ));
    }

    private function syncRecipients(
        Message $message,
        array $raw
    ): void {
        $types = ['to', 'cc', 'bcc'];
        foreach ($types as $type) {
            foreach (($raw[$type] ?? []) as $recipient) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'type' => $type,
                    'email' => $recipient['email'] ?? '',
                    'name' => $recipient['name'] ?? null,
                ]);
            }
        }
    }

    private function syncAttachmentMetadata(
        Message $message,
        array $raw
    ): void {
        foreach (($raw['attachments'] ?? []) as $att) {
            Attachment::create([
                'message_id' => $message->id,
                'filename' => $att['filename'] ?? 'attachment',
                'mime_type' => $att['mime_type'] ?? 'application/octet-stream',
                'size' => $att['size'] ?? 0,
                'imap_part_number' => $att['part_number'] ?? null,
                'is_downloaded' => false,
            ]);
        }
    }

    private function updateThreadStats(Thread $thread): void
    {
        $thread->update([
            'message_count' => $thread->messages()->count(),
            'has_unread' => $thread->messages()
                ->where('is_read', false)->exists(),
            'last_message_at' => $thread->messages()
                ->max('received_at') ?? now(),
        ]);
    }
}
