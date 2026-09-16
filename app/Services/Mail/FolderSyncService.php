<?php

namespace App\Services\Mail;

use App\Models\EmailAccount;
use App\Models\Folder;

class FolderSyncService
{
    private array $typeMap = [
        'INBOX' => 'inbox',
        'Sent' => 'sent',
        'Sent Items' => 'sent',
        'Sent Messages' => 'sent',
        'Drafts' => 'drafts',
        'Draft' => 'drafts',
        'Trash' => 'trash',
        'Deleted' => 'trash',
        'Deleted Items' => 'trash',
        'Spam' => 'spam',
        'Junk' => 'spam',
        'Junk Email' => 'spam',
        'Archive' => 'archive',
        'Archives' => 'archive',
    ];

    public function __construct(
        protected ImapClient $client
    ) {}

    public function syncFolders(EmailAccount $account): array
    {
        $imapFolders = $this->client->getFolders();
        $synced = [];

        foreach ($imapFolders as $imapFolder) {
            $type = $this->detectType($imapFolder['name']);

            $folder = Folder::updateOrCreate(
                [
                    'email_account_id' => $account->id,
                    'imap_name' => $imapFolder['imap_name'],
                ],
                [
                    'name' => $imapFolder['name'],
                    'type' => $type,
                ]
            );

            $synced[] = $folder;
        }

        return $synced;
    }

    private function detectType(string $name): string
    {
        foreach ($this->typeMap as $pattern => $type) {
            if (strcasecmp($name, $pattern) === 0) {
                return $type;
            }
        }

        return 'custom';
    }
}
