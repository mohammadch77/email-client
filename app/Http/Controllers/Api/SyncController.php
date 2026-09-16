<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends ApiController
{
    public function syncAccount(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        SyncEmailAccountJob::dispatch($account->id);

        return $this->successResponse(null, 'Sync started', 202);
    }
}
