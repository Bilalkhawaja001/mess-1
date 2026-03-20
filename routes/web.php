<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportCenterController;
use App\Http\Controllers\Admin\ExtraController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\KitchenController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\LedgerToolchainController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MonthGovernanceController;
use App\Http\Controllers\Admin\MonthlyAttendanceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProcurementController;
use App\Http\Controllers\Admin\RateController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatementController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MemberAccountController;
use App\Http\Controllers\Auth\MemberRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\PaymentController as MemberPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('/password-reset/request', [AuthController::class, 'requestPasswordReset'])->name('password-reset.request.public');
    Route::post('/password-reset/consume', [AuthController::class, 'consumePasswordReset'])->name('password-reset.consume.public');

    Route::get('/register/member', [MemberRegistrationController::class, 'showStart'])->name('member.register.start');
    Route::post('/register/member', [MemberRegistrationController::class, 'start'])->middleware('throttle:3,1')->name('member.register.start.submit');
    Route::get('/register/member/verify', [MemberRegistrationController::class, 'showVerify'])->name('member.register.verify');
    Route::post('/register/member/verify', [MemberRegistrationController::class, 'verify'])->middleware('throttle:8,1')->name('member.register.verify.submit');
    Route::post('/register/member/resend', [MemberRegistrationController::class, 'resend'])->middleware('throttle:4,1')->name('member.register.resend');
    Route::get('/register/member/complete', [MemberRegistrationController::class, 'showComplete'])->name('member.register.complete');
    Route::post('/register/member/complete', [MemberRegistrationController::class, 'complete'])->name('member.register.complete.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth'])->name('logout');

Route::get('/', function () {
    if (! auth()->check()) return redirect()->route('login');
    return auth()->user()->isMemberRole() ? redirect()->route('member.dashboard') : redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:SUPER_ADMIN,ADMIN,DATA_ENTRY,AUDITOR', 'must_change_password'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::post('/auth/password-reset/request', [AuthController::class, 'requestPasswordReset'])->name('auth.password-reset.request');
    Route::post('/auth/password-reset/consume', [AuthController::class, 'consumePasswordReset'])->name('auth.password-reset.consume');
    Route::get('/auth/password-change', [AuthController::class, 'showChangePasswordForm'])->name('auth.password-change.form');
    Route::post('/auth/password-change', [AuthController::class, 'changePassword'])->name('auth.password-change');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{member}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');
    Route::post('/members/{member}/deactivate', [MemberController::class, 'deactivate'])->name('members.deactivate');
    Route::post('/members/{member}/reactivate', [MemberController::class, 'reactivate'])->name('members.reactivate');
    Route::post('/members/{member}/remove', [MemberController::class, 'remove'])->name('members.remove');
    Route::post('/members/import', [MemberController::class, 'import'])->name('members.import');
    Route::get('/members/sample-csv', [MemberController::class, 'sampleCsv'])->name('members.sample-csv');

    Route::prefix('/member-accounts')->name('member-accounts.')->middleware('permission:superadmin.member_account_create')->group(function () {
        Route::get('/', [MemberAccountController::class, 'index'])->name('index');
        Route::post('/', [MemberAccountController::class, 'store'])->name('store');
        Route::post('/{member}/activate', [MemberAccountController::class, 'activate'])->middleware('permission:superadmin.member_account_activate')->name('activate');
        Route::post('/{member}/deactivate', [MemberAccountController::class, 'deactivate'])->middleware('permission:superadmin.member_account_deactivate')->name('deactivate');
        Route::post('/{member}/reset', [MemberAccountController::class, 'reset'])->middleware('permission:superadmin.member_account_reset')->name('reset');
        Route::post('/{member}/unlock-otp', [MemberAccountController::class, 'unlockOtp'])->middleware('permission:superadmin.member_account_reset')->name('unlock-otp');
        Route::post('/{member}/mark-mobile-verified', [MemberAccountController::class, 'markMobileVerified'])->middleware('permission:superadmin.member_account_activate')->name('mark-mobile-verified');
    });

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance-monthly', [MonthlyAttendanceController::class, 'index'])->name('attendance-monthly.index');
    Route::post('/attendance-monthly', [MonthlyAttendanceController::class, 'store'])->name('attendance-monthly.store');
    Route::post('/attendance-monthly/approve', [MonthlyAttendanceController::class, 'approve'])->name('attendance-monthly.approve');
    Route::post('/attendance-monthly/unlock', [MonthlyAttendanceController::class, 'unlock'])->name('attendance-monthly.unlock');
    Route::get('/attendance-monthly/export', [MonthlyAttendanceController::class, 'export'])->name('attendance-monthly.export');

    Route::get('/extras', [ExtraController::class, 'index'])->name('extras.index');
    Route::post('/extras', [ExtraController::class, 'store'])->name('extras.store');

    Route::get('/rates', [RateController::class, 'index'])->name('rates.index');
    Route::post('/rates', [RateController::class, 'store'])->name('rates.store');
    Route::post('/rates/{rate}/toggle-approve', [RateController::class, 'toggleApprove'])->name('rates.toggle-approve');
    Route::post('/rates/{rate}/toggle-active', [RateController::class, 'toggleActive'])->name('rates.toggle-active');
    Route::post('/rates/{rate}/toggle-lock', [RateController::class, 'toggleLock'])->name('rates.toggle-lock');
    Route::post('/rates/{rate}/update', [RateController::class, 'update'])->name('rates.update.legacy');
    Route::post('/rates/{rate}/delete', [RateController::class, 'destroy'])->name('rates.delete.legacy');
    Route::post('/rates/import', [RateController::class, 'import'])->name('rates.import');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/generate', [BillingController::class, 'generate'])->name('billing.generate');
    Route::post('/billing/{billing}/correct', [BillingController::class, 'correct'])->name('billing.correct');

    Route::middleware('permission:payments.view_admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    });
    Route::middleware('permission:payments.manual_record_admin')->group(function () {
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    });
    Route::middleware('permission:payments.override_status_admin')->group(function () {
        Route::post('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    });
    Route::middleware('permission:payments.verify_admin')->group(function () {
        Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/transactions/{transaction}/verify', [PaymentController::class, 'verifyTransaction'])->name('payments.transactions.verify');
    });
    Route::middleware('permission:payments.reconcile_admin')->group(function () {
        Route::post('/payments/reconciliations/{reconciliation}/reconcile', [PaymentController::class, 'reconcile'])->name('payments.reconciliations.reconcile');
    });

    Route::get('/month-governance', [MonthGovernanceController::class, 'index'])->name('month.index');
    Route::post('/month-governance/close', [MonthGovernanceController::class, 'close'])->name('month.close');
    Route::post('/month-governance/reopen', [MonthGovernanceController::class, 'reopen'])->name('month.reopen');
    Route::post('/month-governance/hard-reset', [MonthGovernanceController::class, 'hardReset'])->name('month.hard-reset');

    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::post('/ledger/adjustments', [LedgerController::class, 'storeAdjustment'])->name('ledger.adjustments.store');
    Route::post('/ledger/import', [LedgerToolchainController::class, 'importLedger'])->name('ledger.import');
    Route::post('/ledger/recompute', [LedgerToolchainController::class, 'recompute'])->name('ledger.recompute');

    Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/statement', [StatementController::class, 'index'])->name('statement.index');

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/items', [InventoryController::class, 'storeItem'])->name('inventory.items.store');
        Route::post('/inventory/transactions', [InventoryController::class, 'storeTxn'])->name('inventory.txns.store');
        Route::post('/inventory/items/import', [InventoryController::class, 'importItems'])->name('inventory.items.import');
    });

    Route::middleware('permission:procurement.manage')->group(function () {
        Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
        Route::post('/procurement/vendors', [ProcurementController::class, 'storeVendor'])->name('procurement.vendors.store');
        Route::post('/procurement/po', [ProcurementController::class, 'storePo'])->name('procurement.po.store');
        Route::post('/procurement/po/{po}/approve', [ProcurementController::class, 'approvePo'])->name('procurement.po.approve');
        Route::post('/procurement/grn', [ProcurementController::class, 'storeGrn'])->name('procurement.grn.store');
        Route::post('/procurement/grn/{grn}/approve', [ProcurementController::class, 'approveGrn'])->name('procurement.grn.approve');
    });

    Route::middleware('permission:kitchen.manage')->group(function () {
        Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('/kitchen/menus', [KitchenController::class, 'storeMenu'])->name('kitchen.menus.store');
        Route::post('/kitchen/recipes', [KitchenController::class, 'storeRecipe'])->name('kitchen.recipes.store');
        Route::post('/kitchen/plans', [KitchenController::class, 'storePlan'])->name('kitchen.plans.store');
        Route::post('/kitchen/issues', [KitchenController::class, 'issue'])->name('kitchen.issues.store');
    });

    Route::middleware('permission:guest.manage')->group(function () {
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::post('/guests', [GuestController::class, 'storeGuest'])->name('guests.store');
        Route::post('/guests/{guest}/edit', [GuestController::class, 'updateGuest'])->name('guests.edit.legacy');
        Route::post('/guests/{guest}/delete', [GuestController::class, 'deleteGuest'])->name('guests.delete.legacy');
        Route::post('/guests/meals', [GuestController::class, 'storeMeal'])->name('guests.meals.store');
        Route::post('/guests/meals/{meal}/update', [GuestController::class, 'updateMeal'])->name('guests.meals.update.legacy');
        Route::post('/guests/meals/{meal}/delete', [GuestController::class, 'deleteMeal'])->name('guests.meals.delete.legacy');
        Route::post('/guests/meals/{meal}/approve', [GuestController::class, 'approveMeal'])->name('guests.meals.approve.legacy');
        Route::get('/guests/meals/export', [GuestController::class, 'exportMeals'])->name('guests.meals.export');
        Route::post('/guests/import', [GuestController::class, 'importGuests'])->name('guests.import');
        Route::post('/guests/meals/import', [GuestController::class, 'importMeals'])->name('guests.meals.import');
    });

    Route::middleware('permission:accounting.manage')->group(function () {
        Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.index');
        Route::post('/accounting/departments', [AccountingController::class, 'storeDepartment'])->name('accounting.departments.store');
        Route::post('/accounting/messes', [AccountingController::class, 'storeMess'])->name('accounting.messes.store');
        Route::post('/accounting/entries', [AccountingController::class, 'storeEntry'])->name('accounting.entries.store');
    });

    Route::middleware('permission:report.export')->group(function () {
        Route::get('/exports', [ExportCenterController::class, 'index'])->name('exports.index');
        Route::get('/exports/stock-ledger', [ExportCenterController::class, 'stockLedger'])->name('exports.stock-ledger');
        Route::get('/exports/guest-meals', [ExportCenterController::class, 'guestMeals'])->name('exports.guest-meals');
        Route::get('/exports/department-ledger', [ExportCenterController::class, 'departmentLedger'])->name('exports.department-ledger');
    });

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::post('/settings/{setting}/toggle', [SettingController::class, 'toggle'])->name('settings.toggle');
});

Route::prefix('member')->name('member.')->middleware(['auth', 'active', 'role:MEMBER'])->group(function () {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::middleware('permission:payments.view_own')->group(function () {
        Route::get('/payments', [MemberPaymentController::class, 'index'])->name('payments.index');
    });
    Route::middleware('permission:payments.initiate_own')->group(function () {
        Route::post('/payments/initiate', [MemberPaymentController::class, 'initiate'])->name('payments.initiate');
    });
});
