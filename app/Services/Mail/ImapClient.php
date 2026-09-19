<?php

namespace App\Services\Mail;

use App\Exceptions\Mail\AttachmentNotFoundException;
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
        if (!$folder) {
            // Try common alternatives
            $alternatives = ['INBOX', 'Inbox', 'inbox'];
            foreach ($alternatives as $alt) {
                if ($alt === $folderName) continue;
                $folder = $this->client->getFolder($alt);
                if ($folder) break;
            }
        }
        if (!$folder) {
            $this->disconnect();
            return [];
        }

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
                'date' => $this->parseDate($msg->getDate()),
                'is_read' => $msg->getFlags()->contains('Seen'),
                'has_attachments' => $msg->hasAttachments(),
            ];
        }

        $this->disconnect();

        return $result;
    }

    public function getMessagesSinceUid(string $folderName, int $sinceUid): array
    {
        $this->connect();
        $folder = $this->client->getFolder($folderName);
        if (!$folder) {
            $this->disconnect();
            return [];
        }

        $messages = $folder->query()
            ->all()
            ->setFetchOrder('asc')
            ->get();

        $result = [];
        foreach ($messages as $msg) {
            if ($msg->getUid() <= $sinceUid) continue;
            $result[] = [
                'uid'             => $msg->getUid(),
                'message_id'      => $msg->getMessageId() ?? '',
                'in_reply_to'     => $msg->getInReplyTo() ?? '',
                'references'      => $msg->getReferences() ?? '',
                'subject'         => (string)($msg->getSubject() ?? ''),
                'from_email'      => $msg->getFrom()[0]->mail ?? '',
                'from_name'       => $msg->getFrom()[0]->personal ?? '',
                'date'            => $this->parseDate($msg->getDate()),
                'is_read'         => $msg->getFlags()->contains('Seen'),
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
        if (!$folder) {
            $this->disconnect();
            throw new \App\Exceptions\Mail\MessageNotFoundException(
                "Folder '$folderName' not found"
            );
        }

        $msgs = $folder->query()
            ->setFetchBody(true)
            ->all()
            ->get();

        $msg = null;
        foreach ($msgs as $m) {
            if ($m->getUid() == $uid) {
                $msg = $m;
                break;
            }
        }

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
            'date' => $this->parseDate($msg->getDate()),
            'is_read' => $msg->getFlags()->contains('Seen'),
            'attachments' => $attachments,
        ];

        $this->disconnect();

        return $result;
    }

    public function downloadAttachment(string $folderName, int $uid, string $partNumber): array
    {
        $this->connect();
        $folder = $this->client->getFolder($folderName);
        if (!$folder) {
            $this->disconnect();
            throw new \App\Exceptions\Mail\MessageNotFoundException(
                "Folder '$folderName' not found"
            );
        }

        $msgs = $folder->query()
            ->setFetchBody(true)
            ->all()
            ->get();

        $msg = null;
        foreach ($msgs as $m) {
            if ($m->getUid() == $uid) {
                $msg = $m;
                break;
            }
        }

        if (! $msg) {
            $this->disconnect();
            throw new MessageNotFoundException("Message UID $uid not found");
        }

        $attachments = $msg->getAttachments();
        $target = null;

        foreach ($attachments as $att) {
            if ((string) $att->getPartNumber() === (string) $partNumber) {
                $target = $att;
                break;
            }
        }

        if (! $target) {
            $this->disconnect();
            throw new AttachmentNotFoundException("Attachment part $partNumber not found");
        }

        $this->disconnect();

        return [
            'filename' => $target->getName() ?? 'attachment',
            'mime_type' => $target->getMimeType() ?? 'application/octet-stream',
            'size' => $target->getSize() ?? 0,
            'content' => $target->getContent(),
        ];
    }

    private function parseDate($date): ?string
    {
        if (!$date) return null;

        try {
            // If it's an Attribute object
            if (method_exists($date, 'first')) {
                $date = $date->first();
            }
            if (method_exists($date, 'getValue')) {
                $date = $date->getValue();
            }
            // If it's a Carbon instance
            if ($date instanceof \Carbon\Carbon) {
                return $date->toDateTimeString();
            }
            // If it's a string
            if (is_string($date)) {
                return \Carbon\Carbon::parse($date)->toDateTimeString();
            }
            // Try toString
            return \Carbon\Carbon::parse((string)$date)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
