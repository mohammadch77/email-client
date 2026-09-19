<?php

namespace App\Services\Mail;

use App\Jobs\MarkMessageFlagJob;
use App\Jobs\SendEmailJob;
use App\Models\EmailAccount;
use App\Models\Folder;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class MailService
{
    public function testConnection(EmailAccount $account): bool
    {
        $client = new ImapClient($account->getImapConfig());

        return $client->testConnection();
    }

    public function syncFolders(EmailAccount $account): array
    {
        $imapClient = new ImapClient($account->getImapConfig());
        $folderSync = new FolderSyncService($imapClient);

        return $folderSync->syncFolders($account);
    }

    public function getMessages(EmailAccount $account, Folder $folder, int $limit = 50, int $page = 1): array
    {
        $messages = Message::where('email_account_id', $account->id)
            ->where('folder_id', $folder->id)
            ->with(['recipients'])
            ->orderBy('received_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $messages->map(fn ($m) => [
            'id'              => $m->id,
            'imap_uid'        => $m->imap_uid,
            'subject'         => $m->subject,
            'from_email'      => $m->from_email,
            'from_name'       => $m->from_name,
            'is_read'         => $m->is_read,
            'is_starred'      => $m->is_starred,
            'has_attachments' => $m->has_attachments,
            'received_at'     => $m->received_at?->toDateTimeString(),
            'sent_at'         => $m->sent_at?->toDateTimeString(),
            'status'          => $m->status,
            'folder_id'       => $m->folder_id,
        ])->toArray();
    }

    public function getMessage(EmailAccount $account, Folder $folder, int $uid): array
    {
        $message = Message::where('email_account_id', $account->id)
            ->where('folder_id', $folder->id)
            ->where('imap_uid', $uid)
            ->with(['recipients', 'attachments'])
            ->first();

        if ($message) {
            return [
                'uid'         => $message->imap_uid,
                'message_id'  => $message->message_id_header,
                'in_reply_to' => $message->in_reply_to,
                'references'  => $message->references,
                'subject'     => $message->subject,
                'from_email'  => $message->from_email,
                'from_name'   => $message->from_name,
                'to'          => $message->recipients
                    ->where('type', 'to')
                    ->map(fn ($r) => [
                        'email' => $r->email,
                        'name'  => $r->name,
                    ])->values()->toArray(),
                'cc' => $message->recipients
                    ->where('type', 'cc')
                    ->map(fn ($r) => [
                        'email' => $r->email,
                        'name'  => $r->name,
                    ])->values()->toArray(),
                'body_text'   => $message->body_text,
                'body_html'   => $message->body_html,
                'date'        => $message->received_at?->toDateTimeString(),
                'is_read'     => $message->is_read,
                'attachments' => $message->attachments->map(fn ($a) => [
                    'id'          => $a->id,
                    'filename'    => $a->filename,
                    'mime_type'   => $a->mime_type,
                    'size'        => $a->size,
                    'part_number' => $a->imap_part_number,
                ])->toArray(),
                'folder_id'   => $message->folder_id,
                'status'      => $message->status,
                'is_starred'  => $message->is_starred,
                'received_at' => $message->received_at?->toDateTimeString(),
            ];
        }

        $client = new ImapClient($account->getImapConfig());

        return $client->getMessage($folder->imap_name, $uid);
    }

    public function searchMessages(
        EmailAccount $account,
        array $filters,
        int $limit = 50,
        int $page = 1
    ): array {
        $query = Message::where('email_account_id', $account->id);

        if (! empty($filters['q'])) {
            $query->whereFullText(
                ['subject', 'body_text', 'from_email'],
                $filters['q']
            );
        }

        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        if (isset($filters['is_starred'])) {
            $query->where('is_starred', (bool) $filters['is_starred']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('received_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->where('received_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['from_email'])) {
            $query->where('from_email', 'like', '%'.$filters['from_email'].'%');
        }

        if (empty($filters['include_drafts'])) {
            $query->where('status', '!=', 'draft');
        }

        $results = $query
            ->with(['folder', 'recipients'])
            ->orderBy('received_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'data' => $results->items(),
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ];
    }

    public function persistMessages(
        EmailAccount $account,
        Folder $folder,
        array $rawMessages
    ): array {
        $persistence = new MessagePersistenceService();
        $persisted = [];

        foreach ($rawMessages as $raw) {
            try {
                $persisted[] = $persistence->persist($account, $folder, $raw);
            } catch (\Exception $e) {
                Log::error('Failed to persist message', [
                    'account_id' => $account->id,
                    'folder' => $folder->imap_name,
                    'uid' => $raw['uid'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $persisted;
    }

    public function sendMessage(EmailAccount $account, array $data): Message
    {
        $sentFolder = $account->folders()
            ->where('type', 'sent')
            ->first();

        if (! $sentFolder) {
            $sentFolder = $account->folders()->create([
                'name' => 'Sent',
                'type' => 'sent',
                'imap_name' => 'Sent',
            ]);
        }

        $message = Message::create([
            'email_account_id' => $account->id,
            'folder_id' => $sentFolder->id,
            'from_email' => $account->email,
            'from_name' => $account->display_name,
            'subject' => $data['subject'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'in_reply_to' => $data['in_reply_to'] ?? null,
            'references' => $data['references'] ?? null,
            'status' => 'sending',
            'sent_at' => now(),
        ]);

        foreach (($data['to'] ?? []) as $r) {
            $message->recipients()->create([
                'type' => 'to',
                'email' => $r['email'],
                'name' => $r['name'] ?? null,
            ]);
        }
        foreach (($data['cc'] ?? []) as $r) {
            $message->recipients()->create([
                'type' => 'cc',
                'email' => $r['email'],
                'name' => $r['name'] ?? null,
            ]);
        }
        foreach (($data['bcc'] ?? []) as $r) {
            $message->recipients()->create([
                'type' => 'bcc',
                'email' => $r['email'],
                'name' => $r['name'] ?? null,
            ]);
        }

        SendEmailJob::dispatch($message->id);

        return $message;
    }

    public function reply(EmailAccount $account, Message $original, array $data): Message
    {
        $replyData = [
            'subject' => 'Re: '.ltrim(
                preg_replace('/^re:\s*/i', '', $original->subject ?? ''),
                ' '
            ),
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'in_reply_to' => $original->message_id_header,
            'references' => trim(
                ($original->references ?? '').' '.
                ($original->message_id_header ?? '')
            ),
            'to' => [[
                'email' => $original->from_email,
                'name' => $original->from_name ?? '',
            ]],
        ];

        return $this->sendMessage($account, $replyData);
    }

    public function replyAll(EmailAccount $account, Message $original, array $data): Message
    {
        $originalTo = $original->recipients
            ->where('type', 'to')
            ->where('email', '!=', $account->email)
            ->map(fn ($r) => ['email' => $r->email, 'name' => $r->name])
            ->toArray();

        $replyAllData = [
            'subject' => 'Re: '.ltrim(
                preg_replace('/^re:\s*/i', '', $original->subject ?? ''),
                ' '
            ),
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'in_reply_to' => $original->message_id_header,
            'references' => trim(
                ($original->references ?? '').' '.
                ($original->message_id_header ?? '')
            ),
            'to' => array_merge(
                [['email' => $original->from_email, 'name' => $original->from_name ?? '']],
                $originalTo
            ),
            'cc' => $original->recipients
                ->where('type', 'cc')
                ->where('email', '!=', $account->email)
                ->map(fn ($r) => ['email' => $r->email, 'name' => $r->name])
                ->toArray(),
        ];

        return $this->sendMessage($account, $replyAllData);
    }

    public function forward(EmailAccount $account, Message $original, array $data): Message
    {
        $fwdData = [
            'subject' => 'Fwd: '.ltrim(
                preg_replace('/^fwd?:\s*/i', '', $original->subject ?? ''),
                ' '
            ),
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'to' => $data['to'] ?? [],
            'cc' => $data['cc'] ?? [],
        ];

        return $this->sendMessage($account, $fwdData);
    }

    public function saveDraft(EmailAccount $account, array $data): Message
    {
        $draftsFolder = $account->folders()
            ->where('type', 'drafts')
            ->first();

        if (! $draftsFolder) {
            $draftsFolder = $account->folders()->create([
                'name' => 'Drafts',
                'type' => 'drafts',
                'imap_name' => 'Drafts',
            ]);
        }

        $draft = Message::create([
            'email_account_id' => $account->id,
            'folder_id' => $draftsFolder->id,
            'from_email' => $account->email,
            'from_name' => $account->display_name,
            'subject' => $data['subject'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'in_reply_to' => $data['in_reply_to'] ?? null,
            'references' => $data['references'] ?? null,
            'status' => 'draft',
        ]);

        foreach (($data['to'] ?? []) as $r) {
            $draft->recipients()->create([
                'type' => 'to',
                'email' => $r['email'],
                'name' => $r['name'] ?? null,
            ]);
        }
        foreach (($data['cc'] ?? []) as $r) {
            $draft->recipients()->create([
                'type' => 'cc',
                'email' => $r['email'],
                'name' => $r['name'] ?? null,
            ]);
        }

        return $draft;
    }

    public function updateDraft(Message $draft, array $data): Message
    {
        if ($draft->status !== 'draft') {
            throw new \InvalidArgumentException('Message is not a draft');
        }

        $draft->update([
            'subject' => $data['subject'] ?? $draft->subject,
            'body_html' => $data['body_html'] ?? $draft->body_html,
            'body_text' => $data['body_text'] ?? $draft->body_text,
        ]);

        return $draft->fresh();
    }

    public function deleteDraft(Message $draft): void
    {
        if ($draft->status !== 'draft') {
            throw new \InvalidArgumentException('Message is not a draft');
        }

        $draft->delete();
    }

    public function sendDraft(EmailAccount $account, Message $draft): Message
    {
        if ($draft->status !== 'draft') {
            throw new \InvalidArgumentException('Message is not a draft');
        }

        $data = [
            'subject' => $draft->subject,
            'body_html' => $draft->body_html,
            'body_text' => $draft->body_text,
            'to' => $draft->recipients
                ->where('type', 'to')
                ->map(fn ($r) => ['email' => $r->email, 'name' => $r->name])
                ->toArray(),
            'cc' => $draft->recipients
                ->where('type', 'cc')
                ->map(fn ($r) => ['email' => $r->email, 'name' => $r->name])
                ->toArray(),
        ];

        $draft->delete();

        return $this->sendMessage($account, $data);
    }

    public function markAsRead(Message $message): void
    {
        $message->update(['is_read' => true]);
        MarkMessageFlagJob::dispatch($message->id, 'read', true);
    }

    public function markAsUnread(Message $message): void
    {
        $message->update(['is_read' => false]);
        MarkMessageFlagJob::dispatch($message->id, 'read', false);
    }

    public function star(Message $message): void
    {
        $message->update(['is_starred' => true]);
    }

    public function unstar(Message $message): void
    {
        $message->update(['is_starred' => false]);
    }

    public function moveToFolder(Message $message, Folder $targetFolder): void
    {
        $oldFolder = $message->folder;
        $message->update(['folder_id' => $targetFolder->id]);

        $this->updateFolderCounts($oldFolder);
        $this->updateFolderCounts($targetFolder);
    }

    public function archive(Message $message): void
    {
        $archiveFolder = $message->emailAccount
            ->folders()->where('type', 'archive')->first();

        if (! $archiveFolder) {
            $archiveFolder = $message->emailAccount->folders()->create([
                'name' => 'Archive',
                'type' => 'archive',
                'imap_name' => 'Archive',
            ]);
        }

        $this->moveToFolder($message, $archiveFolder);
    }

    public function delete(Message $message): void
    {
        if ($message->folder->type === 'trash') {
            $message->delete();

            return;
        }

        $trashFolder = $message->emailAccount
            ->folders()->where('type', 'trash')->first();

        if (! $trashFolder) {
            $trashFolder = $message->emailAccount->folders()->create([
                'name' => 'Trash',
                'type' => 'trash',
                'imap_name' => 'Trash',
            ]);
        }

        $this->moveToFolder($message, $trashFolder);
    }

    public function restore(Message $message): void
    {
        $inbox = $message->emailAccount
            ->folders()->where('type', 'inbox')->firstOrFail();

        $this->moveToFolder($message, $inbox);
    }

    private function updateFolderCounts(Folder $folder): void
    {
        $folder->update([
            'total_count' => $folder->messages()->count(),
            'unread_count' => $folder->messages()->where('is_read', false)->count(),
        ]);
    }
}
