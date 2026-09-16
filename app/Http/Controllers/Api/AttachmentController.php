<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Mail\AttachmentNotFoundException;
use App\Models\Attachment;
use App\Models\EmailAccount;
use App\Models\Message;
use App\Services\Mail\AttachmentService;
use App\Services\Mail\ImapClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends ApiController
{
    public function index(Request $request, EmailAccount $account, int $uid): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $message = Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();

        return $this->successResponse($message->attachments);
    }

    public function download(Request $request, EmailAccount $account, int $uid, Attachment $attachment): BinaryFileResponse|JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $message = Message::where('email_account_id', $account->id)
            ->where('imap_uid', $uid)
            ->firstOrFail();

        abort_if($attachment->message_id !== $message->id, 403);

        try {
            $imapClient = new ImapClient($account->getImapConfig());
            $service = new AttachmentService();
            $attachment = $service->download($attachment, $imapClient);

            $path = $service->getStoragePath($attachment);

            return response()->download(
                $path,
                $attachment->filename,
                ['Content-Type' => $attachment->mime_type]
            );
        } catch (AttachmentNotFoundException $e) {
            return $this->errorResponse('Attachment not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Download failed', 500);
        }
    }
}
