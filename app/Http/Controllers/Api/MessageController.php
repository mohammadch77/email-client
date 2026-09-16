<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Mail\MessageNotFoundException;
use App\Models\EmailAccount;
use App\Models\Folder;
use App\Models\Message;
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

    public function search(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'folder_id' => 'nullable|integer',
            'is_read' => 'nullable|boolean',
            'is_starred' => 'nullable|boolean',
            'from_email' => 'nullable|email',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'include_drafts' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if (empty(array_filter($request->only([
            'q', 'folder_id', 'is_read', 'is_starred',
            'from_email', 'from_date', 'to_date',
        ])))) {
            return $this->errorResponse('Provide at least one search filter', 422);
        }

        $results = app(MailService::class)->searchMessages(
            $account,
            $validated,
            $validated['limit'] ?? 50,
            $validated['page'] ?? 1
        );

        return $this->successResponse($results);
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

    public function reply(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $original = Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();
        $original->load('recipients');

        $validated = $request->validate([
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
        ]);

        $result = app(MailService::class)->reply($account, $original, $validated);

        return $this->successResponse($result, 'Reply queued', 202);
    }

    public function replyAll(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $original = Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();
        $original->load('recipients');

        $validated = $request->validate([
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
        ]);

        $result = app(MailService::class)->replyAll($account, $original, $validated);

        return $this->successResponse($result, 'Reply queued', 202);
    }

    public function forward(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $original = Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();
        $original->load('recipients');

        $validated = $request->validate([
            'to' => 'required|array|min:1',
            'to.*.email' => 'required|email',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
        ]);

        $result = app(MailService::class)->forward($account, $original, $validated);

        return $this->successResponse($result, 'Forward queued', 202);
    }

    public function saveDraft(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'subject' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'to' => 'nullable|array',
            'to.*.email' => 'email',
        ]);

        $draft = app(MailService::class)->saveDraft($account, $validated);

        return $this->successResponse($draft, 'Draft saved', 201);
    }

    public function updateDraft(Request $request, EmailAccount $account, Message $message): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);
        abort_if($message->emailAccount->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'subject' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'to' => 'nullable|array',
            'to.*.email' => 'email',
        ]);

        $draft = app(MailService::class)->updateDraft($message, $validated);

        return $this->successResponse($draft, 'Draft updated');
    }

    public function destroyDraft(Request $request, EmailAccount $account, Message $message): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);
        abort_if($message->emailAccount->user_id !== $request->user()->id, 403);

        app(MailService::class)->deleteDraft($message);

        return $this->successResponse(null, 'Draft deleted');
    }

    public function sendDraft(Request $request, EmailAccount $account, Message $message): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);
        abort_if($message->emailAccount->user_id !== $request->user()->id, 403);

        $result = app(MailService::class)->sendDraft($account, $message);

        return $this->successResponse($result, 'Draft queued', 202);
    }

    private function findMessage(EmailAccount $account, int $uid): Message
    {
        return Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();
    }

    private function authorizeAccount(Request $request, EmailAccount $account): void
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    public function markRead(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->markAsRead($message);

        return $this->successResponse(null, 'Marked read');
    }

    public function markUnread(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->markAsUnread($message);

        return $this->successResponse(null, 'Marked unread');
    }

    public function star(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->star($message);

        return $this->successResponse(null, 'Starred');
    }

    public function unstar(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->unstar($message);

        return $this->successResponse(null, 'Unstarred');
    }

    public function move(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'folder_id' => 'required|integer',
        ]);

        $folder = Folder::where('id', $validated['folder_id'])
            ->where('email_account_id', $account->id)
            ->firstOrFail();

        $message = $this->findMessage($account, $uid);

        app(MailService::class)->moveToFolder($message, $folder);

        return $this->successResponse(null, 'Moved');
    }

    public function archive(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->archive($message);

        return $this->successResponse(null, 'Archived');
    }

    public function destroy(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->delete($message);

        return $this->successResponse(null, 'Deleted');
    }

    public function restore(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        $message = $this->findMessage($account, $uid);

        app(MailService::class)->restore($message);

        return $this->successResponse(null, 'Restored');
    }
}
