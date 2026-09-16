<?php

namespace App\Services\Mail;

use App\Models\EmailAccount;
use App\Models\Folder;

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
}
