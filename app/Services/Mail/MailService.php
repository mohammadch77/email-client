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
}
