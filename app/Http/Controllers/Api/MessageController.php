<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Mail\MessageNotFoundException;
use App\Models\EmailAccount;
use App\Models\Folder;
use App\Services\Mail\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends ApiController
{
    public function index(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'folder_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $folder = Folder::where('id', $validated['folder_id'])
            ->where('email_account_id', $account->id)
            ->firstOrFail();

        $messages = app(MailService::class)->getMessages(
            $account,
            $folder,
            $validated['limit'] ?? 50,
            $validated['page'] ?? 1
        );

        return $this->successResponse($messages);
    }

    public function show(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'folder_id' => 'required|integer',
        ]);

        $folder = Folder::where('id', $validated['folder_id'])
            ->where('email_account_id', $account->id)
            ->firstOrFail();

        try {
            $message = app(MailService::class)->getMessage($account, $folder, $uid);

            return $this->successResponse($message);
        } catch (MessageNotFoundException $e) {
            return $this->errorResponse('Message not found', 404);
        }
    }
}
