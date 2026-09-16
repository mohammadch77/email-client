<?php

namespace App\Services\Mail;

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
        $client = new ImapClient($account->getImapConfig());

        return $client->getMessages($folder->imap_name, $limit, $page);
    }

    public function getMessage(EmailAccount $account, Folder $folder, int $uid): array
    {
        $client = new ImapClient($account->getImapConfig());

        return $client->getMessage($folder->imap_name, $uid);
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
}
