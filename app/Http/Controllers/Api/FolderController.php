<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Mail\ConnectionFailedException;
use App\Models\EmailAccount;
use App\Services\Mail\MailService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends ApiController
{
    public function index(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $folders = $account->folders()->orderBy('type')->get();

        return $this->successResponse($folders, '', 200);
    }

    public function sync(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        try {
            $folders = app(MailService::class)->syncFolders($account);

            return $this->successResponse($folders, 'Folders synced', 200);
        } catch (ConnectionFailedException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (Exception $e) {
            return $this->errorResponse('Sync failed', 500);
        }
    }
}
