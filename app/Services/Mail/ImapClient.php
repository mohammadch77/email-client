<?php

namespace App\Services\Mail;

use App\Exceptions\Mail\ConnectionFailedException;
use App\Exceptions\Mail\MessageNotFoundException;
use Exception;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

class ImapClient
{
    protected Client $client;

    public function __construct(array $config)
    {
        $manager = new ClientManager();
        $this->client = $manager->make([
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'username' => $config['username'],
            'password' => $config['password'],
            'protocol' => 'imap',
            'validate_cert' => true,
        ]);
    }

    public function connect(): void
    {
        $this->client->connect();
    }

    public function disconnect(): void
    {
        try {
            $this->client->disconnect();
        } catch (Exception $e) {
            // ignore disconnect errors
        }
    }

    public function testConnection(): bool
    {
        try {
            $this->connect();
            $this->disconnect();

            return true;
        } catch (Exception $e) {
            throw new ConnectionFailedException(
                'IMAP connection failed: '.$e->getMessage()
            );
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getFolders(): array
    {
        $this->connect();
        $folders = $this->client->getFolders(false);
        $result = [];

        foreach ($folders as $folder) {
            $result[] = [
                'name' => $folder->name,
                'imap_name' => $folder->full_name ?? $folder->name,
                'delimiter' => $folder->delimiter ?? '/',
            ];
        }

        $this->disconnect();

        return $result;
    }

    public function getMessages(string $folderName, int $limit = 50, int $page = 1): array
    {
        $this->connect();
        $folder = $this->client->getFolder($folderName);

        $messages = $folder->query()
            ->all()
            ->setFetchOrder('desc')
            ->paginate($limit, $page);

        $result = [];
        foreach ($messages as $msg) {
            $result[] = [
                'uid' => $msg->getUid(),
                'message_id' => $msg->getMessageId() ?? '',
                'in_reply_to' => $msg->getInReplyTo() ?? '',
                'references' => $msg->getReferences() ?? '',
                'subject' => (string) ($msg->getSubject() ?? ''),
                'from_email' => $msg->getFrom()[0]->mail ?? '',
                'from_name' => $msg->getFrom()[0]->personal ?? '',
                'date' => $msg->getDate()?->toDateTimeString(),
                'is_read' => $msg->getFlags()->contains('Seen'),
                'has_attachments' => $msg->hasAttachments(),
            ];
        }

        $this->disconnect();

        return $result;
    }

    public function getMessage(string $folderName, int $uid): array
    {
        $this->connect();
        $folder = $this->client->getFolder($folderName);

        $msg = $folder->query()
            ->uid($uid)
            ->setFetchBody(true)
            ->first();

        if (! $msg) {
            $this->disconnect();
            throw new MessageNotFoundException("Message UID $uid not found");
        }

        $to = [];
        foreach (($msg->getTo() ?? []) as $r) {
            $to[] = ['email' => $r->mail, 'name' => $r->personal ?? ''];
        }
        $cc = [];
        foreach (($msg->getCc() ?? []) as $r) {
            $cc[] = ['email' => $r->mail, 'name' => $r->personal ?? ''];
        }

        $attachments = [];
        foreach ($msg->getAttachments() as $att) {
            $attachments[] = [
                'filename' => $att->getName() ?? 'attachment',
                'mime_type' => $att->getMimeType() ?? 'application/octet-stream',
                'size' => $att->getSize() ?? 0,
                'part_number' => $att->getPartNumber() ?? '',
            ];
        }

        $result = [
            'uid' => $msg->getUid(),
            'message_id' => $msg->getMessageId() ?? '',
            'in_reply_to' => $msg->getInReplyTo() ?? '',
            'references' => $msg->getReferences() ?? '',
            'subject' => (string) ($msg->getSubject() ?? ''),
            'from_email' => $msg->getFrom()[0]->mail ?? '',
            'from_name' => $msg->getFrom()[0]->personal ?? '',
            'to' => $to,
            'cc' => $cc,
            'body_text' => $msg->getTextBody() ?? '',
            'body_html' => $msg->getHtmlBody() ?? '',
            'date' => $msg->getDate()?->toDateTimeString(),
            'is_read' => $msg->getFlags()->contains('Seen'),
            'attachments' => $attachments,
        ];

        $this->disconnect();

        return $result;
    }
}
