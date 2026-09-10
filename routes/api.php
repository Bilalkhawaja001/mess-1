<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\Member\AlfaPaymentController;

Route::prefix('member')->group(function () {
    Route::post('/login', [MemberApiController::class, 'login'])->middleware('throttle:120,1');
    Route::post('/forgot-password/request', [MemberApiController::class, 'forgotPasswordRequest'])->middleware('throttle:30,1');
    Route::post('/forgot-password/reset', [MemberApiController::class, 'forgotPasswordReset'])->middleware('throttle:120,1');

    Route::middleware('throttle:600,1')->group(function () {
        Route::get('/profile', [MemberApiController::class, 'profile']);
        Route::post('/change-password', [MemberApiController::class, 'changePassword'])->middleware('throttle:120,1');
        Route::post('/email/request-otp', [MemberApiController::class, 'requestEmailOtp'])->middleware('throttle:60,1');
        Route::post('/email/verify-otp', [MemberApiController::class, 'verifyEmailOtp'])->middleware('throttle:60,1');
        Route::post('/profile/change-requests', [MemberApiController::class, 'storeProfileChangeRequest'])->middleware('throttle:60,1');
        Route::post('/profile-change-request', [MemberApiController::class, 'storeProfileChangeRequest'])->middleware('throttle:60,1');
        Route::get('/dashboard', [MemberApiController::class, 'dashboard']);
        Route::get('/notifications', [MemberApiController::class, 'notifications']);
        Route::post('/notifications/mark-read', [MemberApiController::class, 'markNotificationsRead'])->middleware('throttle:20,1');
        Route::get('/bill/current', [MemberApiController::class, 'currentBill']);
        Route::get('/statement', [MemberApiController::class, 'statement']);
        Route::get('/menu/today', [MemberApiController::class, 'todayMenu']);
        Route::get('/payments', [MemberApiController::class, 'payments']);
        Route::post('/payments/upload', [MemberApiController::class, 'uploadPayment'])->middleware('throttle:60,1');
        Route::get('/payments/options', [AlfaPaymentController::class, 'options']);
        Route::post('/payments/preview', [AlfaPaymentController::class, 'preview'])->middleware('throttle:60,1');
        Route::post('/payments/create', [AlfaPaymentController::class, 'create'])->middleware('throttle:30,1');
        Route::get('/payments/{transaction}/status', [AlfaPaymentController::class, 'status']);
        Route::get('/complaints', [MemberApiController::class, 'complaints']);
        Route::post('/complaints', [MemberApiController::class, 'createComplaint'])->middleware('throttle:60,1');
    });
});

Route::prefix('kitchen')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Kitchen\KitchenAuthController::class, 'login'])->middleware('throttle:60,1');

    Route::middleware('throttle:600,1')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\Kitchen\KitchenAuthController::class, 'profile']);
        Route::post('/logout', [\App\Http\Controllers\Api\Kitchen\KitchenAuthController::class, 'logout']);

        Route::get('/vendors/search', [\App\Http\Controllers\Api\Kitchen\KitchenPoController::class, 'searchVendors']);
        Route::get('/items/search', [\App\Http\Controllers\Api\Kitchen\KitchenPoController::class, 'searchItems']);
        Route::get('/purchase-orders', [\App\Http\Controllers\Api\Kitchen\KitchenPoController::class, 'index']);
        Route::post('/purchase-orders', [\App\Http\Controllers\Api\Kitchen\KitchenPoController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/purchase-orders/{id}', [\App\Http\Controllers\Api\Kitchen\KitchenPoController::class, 'show'])->whereNumber('id');

        Route::get('/grn/eligible-pos', [\App\Http\Controllers\Api\Kitchen\KitchenGrnController::class, 'eligiblePos']);
        Route::get('/grn', [\App\Http\Controllers\Api\Kitchen\KitchenGrnController::class, 'index']);
        Route::post('/grn', [\App\Http\Controllers\Api\Kitchen\KitchenGrnController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/grn/{id}', [\App\Http\Controllers\Api\Kitchen\KitchenGrnController::class, 'show'])->whereNumber('id');

        Route::post('/outstanding', [\App\Http\Controllers\Api\Kitchen\KitchenOutstandingController::class, 'lookup'])->middleware('throttle:60,1');
    });
});

Route::prefix('admin-app')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AdminApp\AdminAuthController::class, 'login'])->middleware('throttle:60,1');

    Route::middleware('throttle:600,1')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\AdminApp\AdminAuthController::class, 'profile']);
        Route::post('/logout', [\App\Http\Controllers\Api\AdminApp\AdminAuthController::class, 'logout']);

        Route::get('/procurement/pending', [\App\Http\Controllers\Api\AdminApp\AdminProcurementController::class, 'pending']);
        Route::post('/procurement/po/{id}/approve', [\App\Http\Controllers\Api\AdminApp\AdminProcurementController::class, 'approvePo'])->whereNumber('id');
        Route::post('/procurement/po/{id}/reject', [\App\Http\Controllers\Api\AdminApp\AdminProcurementController::class, 'rejectPo'])->whereNumber('id');
        Route::post('/procurement/grn/{id}/approve', [\App\Http\Controllers\Api\AdminApp\AdminProcurementController::class, 'approveGrn'])->whereNumber('id');
        Route::post('/procurement/grn/{id}/reject', [\App\Http\Controllers\Api\AdminApp\AdminProcurementController::class, 'rejectGrn'])->whereNumber('id');
    });
});
