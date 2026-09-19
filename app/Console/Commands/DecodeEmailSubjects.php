<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class DecodeEmailSubjects extends Command
{
    protected $signature = 'mail:decode-subjects';

    protected $description = 'Decode MIME-encoded subjects in database';

    public function handle(): void
    {
        $messages = Message::whereNotNull('subject')
            ->where('subject', 'like', '=?%')
            ->get();

        $this->info("Found {$messages->count()} encoded subjects");

        foreach ($messages as $message) {
            $decoded = mb_decode_mimeheader($message->subject);
            if ($decoded && $decoded !== $message->subject) {
                $message->update(['subject' => $decoded]);
            }
        }

        $names = Message::whereNotNull('from_name')
            ->where('from_name', 'like', '=?%')
            ->get();

        $this->info("Found {$names->count()} encoded sender names");

        foreach ($names as $message) {
            $decoded = mb_decode_mimeheader($message->from_name);
            if ($decoded && $decoded !== $message->from_name) {
                $message->update(['from_name' => $decoded]);
            }
        }

        $this->info('Done!');
    }
}
