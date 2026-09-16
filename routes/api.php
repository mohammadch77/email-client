<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailAccountController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('email-accounts', EmailAccountController::class);
    Route::post('email-accounts/{account}/test-connection', [EmailAccountController::class, 'testConnection']);
    Route::get('email-accounts/{account}/folders', [FolderController::class, 'index']);
    Route::post('email-accounts/{account}/folders/sync', [FolderController::class, 'sync']);
    Route::get('email-accounts/{account}/messages', [MessageController::class, 'index']);
    Route::get('email-accounts/{account}/messages/{uid}', [MessageController::class, 'show']);
    Route::post('email-accounts/{account}/messages/send', [MessageController::class, 'send']);
    Route::post('email-accounts/{account}/messages/{uid}/reply', [MessageController::class, 'reply']);
    Route::post('email-accounts/{account}/messages/{uid}/reply-all', [MessageController::class, 'replyAll']);
    Route::post('email-accounts/{account}/messages/{uid}/forward', [MessageController::class, 'forward']);
    Route::post('email-accounts/{account}/drafts', [MessageController::class, 'saveDraft']);
    Route::put('email-accounts/{account}/drafts/{message}', [MessageController::class, 'updateDraft']);
    Route::delete('email-accounts/{account}/drafts/{message}', [MessageController::class, 'destroyDraft']);
    Route::post('email-accounts/{account}/drafts/{message}/send', [MessageController::class, 'sendDraft']);
    Route::post('email-accounts/{account}/messages/{uid}/read', [MessageController::class, 'markRead']);
    Route::post('email-accounts/{account}/messages/{uid}/unread', [MessageController::class, 'markUnread']);
    Route::post('email-accounts/{account}/messages/{uid}/star', [MessageController::class, 'star']);
    Route::post('email-accounts/{account}/messages/{uid}/unstar', [MessageController::class, 'unstar']);
    Route::post('email-accounts/{account}/messages/{uid}/move', [MessageController::class, 'move']);
    Route::post('email-accounts/{account}/messages/{uid}/archive', [MessageController::class, 'archive']);
    Route::delete('email-accounts/{account}/messages/{uid}', [MessageController::class, 'destroy']);
    Route::post('email-accounts/{account}/messages/{uid}/restore', [MessageController::class, 'restore']);
    Route::post('email-accounts/{account}/sync', [SyncController::class, 'syncAccount']);
    Route::get('email-accounts/{account}/sync/status', [SyncController::class, 'status']);
});
