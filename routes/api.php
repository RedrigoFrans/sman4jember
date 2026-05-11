<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\LoanApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\EbookApiController;
use App\Http\Controllers\Api\VisitApiController;
use App\Http\Controllers\Api\ReadingProgressController;
use App\Http\Controllers\Api\ChatbotApiController;

// ─── Public API (tanpa auth) ──────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/auth/login',          [AuthApiController::class, 'login']);
    Route::post('/auth/register',       [AuthApiController::class, 'register']);
    Route::post('/auth/claim-lookup',   [AuthApiController::class, 'claimLookup']);
    Route::post('/auth/claim-activate', [AuthApiController::class, 'claimActivate']);

    // Forgot Password (OTP)
    Route::post('/auth/forgot-password/send-otp',   [AuthApiController::class, 'forgotSendOtp']);
    Route::post('/auth/forgot-password/verify-otp',  [AuthApiController::class, 'forgotVerifyOtp']);
    Route::post('/auth/forgot-password/reset',       [AuthApiController::class, 'forgotResetPassword']);

    // Katalog buku publik
    Route::get('/books',         [BookApiController::class, 'index']);
    Route::get('/books/{book}',  [BookApiController::class, 'show']);

    // ─── Auth required ────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/auth/me',      [AuthApiController::class, 'me']);

        // Member profile
        Route::get('/member/profile',    [MemberApiController::class, 'profile']);
        Route::put('/member/profile',    [MemberApiController::class, 'updateProfile']);
        Route::post('/member/fcm-token', [MemberApiController::class, 'storeFcmToken']);

        // Pinjaman
        Route::get('/loans',             [LoanApiController::class, 'index']);
        Route::get('/loans/{loan}',      [LoanApiController::class, 'show']);
        Route::post('/loans/{loan}/extend', [LoanApiController::class, 'extend']);

        // Notifikasi
        Route::get('/notifications',              [NotificationApiController::class, 'index']);
        Route::post('/notifications/{id}/read',   [NotificationApiController::class, 'markRead']);
        Route::post('/notifications/read-all',    [NotificationApiController::class, 'markAllRead']);

        // Kunjungan (scan QR presensi via mobile)
        Route::post('/visits', [VisitApiController::class, 'store']);

        // E-Book
        Route::prefix('ebooks')->name('api.ebooks.')->group(function () {
            Route::get('/search',                     [EbookApiController::class, 'search']);
            Route::get('/{source}/{externalId}',      [EbookApiController::class, 'show']);
            Route::get('/bookmarks',                  [EbookApiController::class, 'bookmarks']);
            Route::post('/bookmarks',                 [EbookApiController::class, 'addBookmark']);
            Route::delete('/bookmarks/{id}',          [EbookApiController::class, 'removeBookmark']);
            Route::put('/bookmarks/{id}/favorite',    [EbookApiController::class, 'toggleFavorite']);
            Route::get('/progress/{source}/{id}',     [EbookApiController::class, 'getProgress']);
            Route::post('/progress',                  [EbookApiController::class, 'saveProgress']);
        });

        //reading progress
        Route::get('/reading-progress', [ReadingProgressController::class, 'index']);
        Route::get('/reading-progress/{ebookId}', [ReadingProgressController::class, 'get']);
        Route::post('/reading-progress/update', [ReadingProgressController::class, 'update']);
        
        Route::prefix('chatbot')->group(function () {
            Route::get('/conversations',              [ChatbotApiController::class, 'conversations']);
            Route::post('/conversations',             [ChatbotApiController::class, 'createConversation']);
            Route::delete('/conversations/{id}',      [ChatbotApiController::class, 'deleteConversation']);
            Route::get('/conversations/{id}/messages', [ChatbotApiController::class, 'messages']);
            Route::post('/send',                      [ChatbotApiController::class, 'send']);
        });
    });
});
