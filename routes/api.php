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
    Route::post('email-accounts/{account}/sync', [SyncController::class, 'syncAccount']);
});
