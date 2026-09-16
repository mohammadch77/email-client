<?php

namespace App\Services\Mail;

use App\Models\EmailAccount;

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
}
