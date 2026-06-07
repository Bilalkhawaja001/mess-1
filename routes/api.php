<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;

Route::prefix('member')->group(function () {
    Route::post('/login', [MemberApiController::class, 'login']);
    Route::get('/profile', [MemberApiController::class, 'profile']);
    Route::post('/profile/change-requests', [MemberApiController::class, 'storeProfileChangeRequest']);
    Route::post('/profile-change-request', [MemberApiController::class, 'storeProfileChangeRequest']);
    Route::get('/dashboard', [MemberApiController::class, 'dashboard']);
    Route::get('/bill/current', [MemberApiController::class, 'currentBill']);
    Route::get('/statement', [MemberApiController::class, 'statement']);
    Route::get('/menu/today', [MemberApiController::class, 'todayMenu']);
    Route::get('/payments', [MemberApiController::class, 'payments']);
    Route::post('/payments/upload', [MemberApiController::class, 'uploadPayment']);
    Route::get('/complaints', [MemberApiController::class, 'complaints']);
    Route::post('/complaints', [MemberApiController::class, 'createComplaint']);
});
