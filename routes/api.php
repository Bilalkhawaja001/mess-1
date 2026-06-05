<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;

Route::prefix('member')->group(function () {
    Route::post('/login', [MemberApiController::class, 'login']);
    Route::get('/profile', [MemberApiController::class, 'profile']);
    Route::get('/dashboard', [MemberApiController::class, 'dashboard']);
    Route::get('/bill/current', [MemberApiController::class, 'currentBill']);
    Route::get('/statement', [MemberApiController::class, 'statement']);
    Route::get('/payments', [MemberApiController::class, 'payments']);
    Route::get('/complaints', [MemberApiController::class, 'complaints']);
    Route::post('/complaints', [MemberApiController::class, 'createComplaint']);
});
