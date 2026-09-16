<?php

namespace App\Http\Controllers\Api;

use App\Models\EmailAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailAccountController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()->emailAccounts()->get();

        return $this->successResponse($accounts, '', 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'min:1'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['nullable', 'in:ssl,tls,none'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls,none'],
        ]);

        $exists = $request->user()->emailAccounts()
            ->where('email', $validated['email'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Account already exists', 422);
        }

        $account = $request->user()->emailAccounts()->create([
            'email' => $validated['email'],
            'display_name' => $validated['display_name'] ?? null,
            'username' => $validated['username'],
            'password' => $validated['password'],
            'imap_host' => $validated['imap_host'] ?? config('mail-client.imap.host'),
            'imap_port' => $validated['imap_port'] ?? config('mail-client.imap.port'),
            'imap_encryption' => $validated['imap_encryption'] ?? config('mail-client.imap.encryption'),
            'smtp_host' => $validated['smtp_host'] ?? config('mail-client.smtp.host'),
            'smtp_port' => $validated['smtp_port'] ?? config('mail-client.smtp.port'),
            'smtp_encryption' => $validated['smtp_encryption'] ?? config('mail-client.smtp.encryption'),
        ]);

        return $this->successResponse($account, 'Account created', 201);
    }

    public function show(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        return $this->successResponse($account, '', 200);
    }

    public function update(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'max:191'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'username' => ['sometimes', 'string', 'max:191'],
            'password' => ['sometimes', 'string', 'min:1'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['nullable', 'in:ssl,tls,none'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls,none'],
        ]);

        $account->update($validated);

        return $this->successResponse($account, 'Account updated', 200);
    }

    public function destroy(Request $request, EmailAccount $account): JsonResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $account->delete();

        return $this->successResponse(null, 'Account deleted', 200);
    }
}
