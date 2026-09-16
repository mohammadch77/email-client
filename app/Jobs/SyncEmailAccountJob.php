<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Models\Folder;
use App\Models\SyncState;
use App\Services\Mail\FolderSyncService;
use App\Services\Mail\ImapClient;
use App\Services\Mail\MessagePersistenceService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEmailAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $accountId
    ) {}

    public function handle(): void
    {
        $account = EmailAccount::find($this->accountId);

        if (! $account || $account->status === 'disconnected') {
            return;
        }

        try {
            $imapClient = new ImapClient($account->getImapConfig());
            $folderSync = new FolderSyncService($imapClient);
            $folders = $folderSync->syncFolders($account);

            $persistence = new MessagePersistenceService;

            foreach ($folders as $folder) {
                $this->syncFolder($account, $folder, $imapClient, $persistence);
            }

            $account->update(['last_synced_at' => now()]);
            $account->markAsActive();
        } catch (\App\Exceptions\Mail\ConnectionFailedException $e) {
            Log::warning('Sync connection failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            $account->markAsError();
            $this->fail($e);
        } catch (Exception $e) {
            Log::error('Sync unexpected error', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }

    private function syncFolder(
        EmailAccount $account,
        Folder $folder,
        ImapClient $imapClient,
        MessagePersistenceService $persistence
    ): void {
        $syncState = SyncState::firstOrCreate(
            [
                'email_account_id' => $account->id,
                'folder_id' => $folder->id,
            ],
            [
                'status' => 'idle',
                'full_sync_done' => false,
            ]
        );

        $syncState->update(['status' => 'running']);

        try {
            if (! $syncState->full_sync_done) {
                $raw = $imapClient->getMessages($folder->imap_name, 100, 1);
            } else {
                $raw = $imapClient->getMessagesSinceUid(
                    $folder->imap_name,
                    $syncState->last_uid ?? 0
                );
            }

            foreach ($raw as $rawMsg) {
                $persistence->persist($account, $folder, $rawMsg);

                if (($rawMsg['uid'] ?? 0) > ($syncState->last_uid ?? 0)) {
                    $syncState->last_uid = $rawMsg['uid'];
                }
            }

            $syncState->update([
                'status' => 'idle',
                'full_sync_done' => true,
                'synced_at' => now(),
                'last_uid' => $syncState->last_uid,
            ]);

            $folder->update([
                'total_count' => $folder->messages()->count(),
                'unread_count' => $folder->messages()->where('is_read', false)->count(),
            ]);
        } catch (Exception $e) {
            $syncState->update(['status' => 'failed']);
            Log::error('Folder sync failed', [
                'account_id' => $account->id,
                'folder' => $folder->imap_name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
