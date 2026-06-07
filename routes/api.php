<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;

Route::prefix('member')->group(function () {
    Route::post('/login', [MemberApiController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/profile', [MemberApiController::class, 'profile']);
        Route::post('/change-password', [MemberApiController::class, 'changePassword'])->middleware('throttle:5,1');
        Route::post('/profile/change-requests', [MemberApiController::class, 'storeProfileChangeRequest'])->middleware('throttle:10,1');
        Route::post('/profile-change-request', [MemberApiController::class, 'storeProfileChangeRequest'])->middleware('throttle:10,1');
        Route::get('/dashboard', [MemberApiController::class, 'dashboard']);
        Route::get('/notifications', [MemberApiController::class, 'notifications']);
        Route::post('/notifications/mark-read', [MemberApiController::class, 'markNotificationsRead'])->middleware('throttle:20,1');
        Route::get('/bill/current', [MemberApiController::class, 'currentBill']);
        Route::get('/statement', [MemberApiController::class, 'statement']);
        Route::get('/menu/today', [MemberApiController::class, 'todayMenu']);
        Route::get('/payments', [MemberApiController::class, 'payments']);
        Route::post('/payments/upload', [MemberApiController::class, 'uploadPayment'])->middleware('throttle:6,1');
        Route::get('/complaints', [MemberApiController::class, 'complaints']);
        Route::post('/complaints', [MemberApiController::class, 'createComplaint'])->middleware('throttle:10,1');
    });
});
