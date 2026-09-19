<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use App\Models\SyncState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends ApiController
{
    public function syncAccount(Request $request, EmailAccount $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $job = new \App\Jobs\SyncEmailAccountJob($account->id);
            $job->handle();
            return $this->successResponse(null, 'Sync completed', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Sync failed: ' . $e->getMessage(), 500);
        }
    }

    public function status(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $states = SyncState::with('folder')
            ->where('email_account_id', $account->id)
            ->get();

        return $this->successResponse([
            'account_id' => $account->id,
            'last_synced_at' => $account->last_synced_at,
            'status' => $account->status,
            'folders' => $states->map(fn ($s) => [
                'folder' => $s->folder?->name,
                'status' => $s->status,
                'last_uid' => $s->last_uid,
                'full_sync_done' => $s->full_sync_done,
                'synced_at' => $s->synced_at,
            ]),
        ]);
    }
}
