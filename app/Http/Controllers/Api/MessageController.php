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

    public function send(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'to' => 'required|array|min:1',
            'to.*.email' => 'required|email',
            'to.*.name' => 'nullable|string',
            'cc' => 'nullable|array',
            'cc.*.email' => 'required_with:cc|email',
            'bcc' => 'nullable|array',
            'bcc.*.email' => 'required_with:bcc|email',
            'subject' => 'nullable|string|max:500',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'in_reply_to' => 'nullable|string',
            'references' => 'nullable|string',
        ]);

        $message = app(MailService::class)->sendMessage($account, $validated);

        return $this->successResponse($message, 'Message queued', 202);
    }
}
