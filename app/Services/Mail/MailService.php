<?php

namespace App\Services\Mail;

use App\Models\EmailAccount;
use App\Models\Folder;
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
}
