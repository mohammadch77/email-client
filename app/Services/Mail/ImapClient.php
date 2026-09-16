<?php

namespace App\Services\Mail;

use App\Exceptions\Mail\ConnectionFailedException;
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
}
