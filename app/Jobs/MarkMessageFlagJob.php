<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Mail\ImapClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarkMessageFlagJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $messageId,
        public readonly string $flag,
        public readonly bool $value
    ) {}

    public function handle(): void
    {
        $message = Message::with(['emailAccount', 'folder'])->find($this->messageId);
        if (! $message) {
            return;
        }

        try {
            $client = new ImapClient($message->emailAccount->getImapConfig());
            $client->connect();

            $folder = $client->getClient()->getFolder($message->folder->imap_name);

            $msg = $folder->query()
                ->uid($message->imap_uid)
                ->setFetchBody(false)
                ->first();

            if (! $msg) {
                return;
            }

            if ($this->flag === 'read') {
                if ($this->value) {
                    $msg->setFlag('Seen');
                } else {
                    $msg->unsetFlag('Seen');
                }
            }

            $client->disconnect();

        } catch (\Exception $e) {
            Log::warning('IMAP flag sync failed', [
                'message_id' => $this->messageId,
                'flag' => $this->flag,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
