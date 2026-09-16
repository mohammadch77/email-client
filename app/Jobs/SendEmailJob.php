<?php

namespace App\Jobs;

use App\Exceptions\Mail\SendFailedException;
use App\Models\Message;
use App\Services\Mail\SmtpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $messageId
    ) {}

    public function handle(): void
    {
        $message = Message::with([
            'emailAccount',
            'recipients',
            'folder',
        ])->find($this->messageId);

        if (! $message) {
            return;
        }

        try {
            $account = $message->emailAccount;

            $data = [
                'from_email' => $account->email,
                'from_name' => $account->display_name ?? $account->email,
                'subject' => $message->subject,
                'body_html' => $message->body_html,
                'body_text' => $message->body_text,
                'to' => $message->recipients
                    ->where('type', 'to')
                    ->map(fn ($r) => [
                        'email' => $r->email,
                        'name' => $r->name,
                    ])->toArray(),
                'cc' => $message->recipients
                    ->where('type', 'cc')
                    ->map(fn ($r) => [
                        'email' => $r->email,
                        'name' => $r->name,
                    ])->toArray(),
                'bcc' => $message->recipients
                    ->where('type', 'bcc')
                    ->map(fn ($r) => [
                        'email' => $r->email,
                        'name' => $r->name,
                    ])->toArray(),
                'in_reply_to' => $message->in_reply_to,
                'references' => $message->references,
            ];

            $smtp = new SmtpClient($account->getSmtpConfig());
            $smtp->send($data);

            $message->update(['status' => 'sent']);
        } catch (SendFailedException $e) {
            Log::error('Send failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);
            $message->update(['status' => 'failed']);
            $this->fail($e);
        }
    }
}
