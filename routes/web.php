<?php

Route::view("/privacy", "privacy")->name("privacy");

/*
|--------------------------------------------------------------------------
| Forced Password Change
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/password/force-change', [\App\Http\Controllers\Auth\ForcedPasswordChangeController::class, 'edit'])
        ->name('password.force.edit');

    Route::post('/password/force-change', [\App\Http\Controllers\Auth\ForcedPasswordChangeController::class, 'update'])
        ->name('password.force.update');
});

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AppSettingsController as AdminAppSettingsController;
use App\Http\Controllers\Api\AppSettingsController as ApiAppSettingsController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportCenterController;
use App\Http\Controllers\Admin\ExtraController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\HubController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\KitchenController;
use App\Http\Controllers\Admin\ComplaintController as AdminComplaintController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\LedgerToolchainController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MessCostingController;
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
use App\Http\Controllers\Member\ComplaintController as MemberComplaintController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\DeviceTokenController as MemberDeviceTokenController;
use App\Http\Controllers\Member\MenuController as MemberMenuController;
use App\Http\Controllers\Member\NotificationController as MemberNotificationController;
use App\Http\Controllers\Member\PaymentController as MemberPaymentController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Member\StatementController as MemberStatementController;
use App\Http\Controllers\Admin\MemberProfileChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::view('/password-recovery', 'auth.password_reset_request')->name('password-reset.request.form');
    Route::view('/password-recovery/reset', 'auth.password_reset')->name('password-reset.form');
    Route::post('/password-reset/request', [AuthController::class, 'requestPasswordReset'])->middleware('throttle:3,1')->name('password-reset.request');
    Route::post('/password-reset/consume', [AuthController::class, 'consumePasswordReset'])->name('password-reset.consume');

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
    $user = auth()->user();
    if ($user->isMemberRole()) {
        return redirect()->route('member.dashboard');
    }
    if (optional($user->role)->code === 'DATA_ENTRY') {
        return redirect()->route('admin.attendance.index');
    }
    return redirect()->route('admin.dashboard');
});

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/api/app-settings', [ApiAppSettingsController::class, 'show'])->name('api.app-settings');
Route::get('/ready', fn () => response()->json(['ready' => true]));

Route::get('/api/whatsapp/webhook', function (\Illuminate\Http\Request $request) {
    $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
    $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
    $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');
    $configuredToken = (string) config('services.whatsapp.verify_token');

    if (
        $mode === 'subscribe' &&
        $configuredToken !== '' &&
        hash_equals($configuredToken, (string) $token)
    ) {
        return response((string) $challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    abort(403, 'Invalid verification token.');
})->name('whatsapp.webhook.verify');

Route::post('/api/whatsapp/webhook', function (\Illuminate\Http\Request $request) {
    $appSecret = (string) config('services.whatsapp.app_secret');
    $providedSignature = (string) $request->header('X-Hub-Signature-256', '');

    if ($appSecret === '' || $providedSignature === '') {
        abort(403, 'Missing webhook signature.');
    }

    $expectedSignature = 'sha256='.hash_hmac(
        'sha256',
        $request->getContent(),
        $appSecret
    );

    if (!hash_equals($expectedSignature, $providedSignature)) {
        abort(403, 'Invalid webhook signature.');
    }

    $payload = $request->json()->all();

    \Illuminate\Support\Facades\Log::info('WhatsApp webhook accepted', [
        'object' => $payload['object'] ?? null,
        'entry_count' => is_array($payload['entry'] ?? null)
            ? count($payload['entry'])
            : 0,
    ]);

    return response()->json(['status' => 'received']);
})->withoutMiddleware([
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
])->name('whatsapp.webhook.receive');

Route::middleware(['auth', 'force_password_change', 'active', 'role:SUPER_ADMIN,ADMIN,AUDITOR'])->group(function () {
    Route::get('/api/menus', [KitchenController::class, 'apiMenus'])->name('api.menus');
    Route::get('/api/guest-rate', [GuestController::class, 'guestRate'])->name('api.guest-rate');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.legacy');
    Route::view('/prototype/sidebar', 'prototypes.sidebar')->name('prototype.sidebar');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'force_password_change', 'active', 'role:SUPER_ADMIN,ADMIN,DATA_ENTRY,AUDITOR', 'must_change_password', 'data_entry_scope'])->group(function () {
    Route::get('/bill-publish', [\App\Http\Controllers\Admin\BillPublishController::class, 'index'])
        ->name('bill-publish.index');
    Route::post('/bill-publish', [\App\Http\Controllers\Admin\BillPublishController::class, 'store'])
        ->name('bill-publish.store');

    Route::get('/announcements', [\App\Http\Controllers\Admin\AnnouncementController::class, 'index'])
        ->name('announcements.index');
    Route::post('/announcements', [\App\Http\Controllers\Admin\AnnouncementController::class, 'store'])
        ->name('announcements.store');

    Route::get('/admin-mess-bill', [\App\Http\Controllers\Admin\AdminMessBillController::class, 'index'])
        ->name('admin-mess-bill.index');
    Route::get('/cost-allocation-preview', [\App\Http\Controllers\Admin\CostAllocationPreviewController::class, 'index'])
        ->name('cost-allocation-preview.index');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/hubs/operations', [HubController::class, 'operations'])->name('hubs.operations');
    Route::get('/hubs/reports', [HubController::class, 'reports'])->name('hubs.reports');
    Route::get('/hubs/inventory', [HubController::class, 'inventory'])->name('hubs.inventory');
    Route::get('/hubs/meals', [HubController::class, 'meals'])->name('hubs.meals');

    Route::post('/auth/password-reset/request', [AuthController::class, 'requestPasswordReset'])->name('auth.password-reset.request');
    Route::post('/auth/password-reset/consume', [AuthController::class, 'consumePasswordReset'])->name('auth.password-reset.consume');
    Route::get('/auth/password-change', [AuthController::class, 'showChangePasswordForm'])->name('auth.password-change.form');
    Route::post('/auth/password-change', [AuthController::class, 'changePassword'])->name('auth.password-change');

    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->middleware('permission:users.toggle')->name('users.toggle-active');

    Route::middleware('permission:member.manage')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::post('/members/{member}/reset-password', [MemberController::class, 'resetPassword'])->middleware('role:SUPER_ADMIN,ADMIN')->name('members.reset-password');
        Route::post('/members/{member}/toggle-active', [MemberController::class, 'toggleActive'])->name('members.toggle-active');
        Route::post('/members/{member}/deactivate', [MemberController::class, 'deactivate'])->name('members.deactivate');
        Route::post('/members/{member}/reactivate', [MemberController::class, 'reactivate'])->name('members.reactivate');
        Route::post('/members/{member}/remove', [MemberController::class, 'remove'])->name('members.remove');
        Route::post('/members/import', [MemberController::class, 'import'])->name('members.import');
        Route::get('/members/sample-csv', [MemberController::class, 'sampleCsv'])->name('members.sample-csv');
    });

    Route::prefix('/member-accounts')->name('member-accounts.')->middleware('permission:superadmin.member_account_create')->group(function () {
        Route::get('/', [MemberAccountController::class, 'index'])->name('index');
        Route::post('/', [MemberAccountController::class, 'store'])->name('store');
        Route::post('/{member}/activate', [MemberAccountController::class, 'activate'])->middleware('permission:superadmin.member_account_activate')->name('activate');
        Route::post('/{member}/deactivate', [MemberAccountController::class, 'deactivate'])->middleware('permission:superadmin.member_account_deactivate')->name('deactivate');
        Route::post('/{member}/reset', [MemberAccountController::class, 'reset'])->middleware('permission:superadmin.member_account_reset')->name('reset');
        Route::post('/{member}/unlock-otp', [MemberAccountController::class, 'unlockOtp'])->middleware('permission:superadmin.member_account_reset')->name('unlock-otp');
        Route::post('/{member}/mark-mobile-verified', [MemberAccountController::class, 'markMobileVerified'])->middleware('permission:superadmin.member_account_activate')->name('mark-mobile-verified');
    });

    Route::middleware('permission:attendance.manage')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance-monthly', [MonthlyAttendanceController::class, 'index'])->name('attendance-monthly.index');
        Route::post('/attendance-monthly', [MonthlyAttendanceController::class, 'store'])->name('attendance-monthly.store');
        Route::get('/attendance-monthly/template', [MonthlyAttendanceController::class, 'template'])->name('attendance-monthly.template');
        Route::post('/attendance-monthly/import', [MonthlyAttendanceController::class, 'import'])->name('attendance-monthly.import');
        Route::post('/attendance-monthly/manual', [MonthlyAttendanceController::class, 'manualStore'])->name('attendance-monthly.manual');
        Route::post('/attendance-monthly/approve', [MonthlyAttendanceController::class, 'approve'])->name('attendance-monthly.approve');
        Route::post('/attendance-monthly/unlock', [MonthlyAttendanceController::class, 'unlock'])->name('attendance-monthly.unlock');
        Route::get('/attendance-monthly/export', [MonthlyAttendanceController::class, 'export'])->name('attendance-monthly.export');
    });

    Route::get('/extras', [ExtraController::class, 'index'])->name('extras.index');
    Route::post('/extras', [ExtraController::class, 'store'])->name('extras.store');

    Route::middleware('permission:rates.manage')->group(function () {
        Route::get('/rates', [RateController::class, 'index'])->name('rates.index');
        Route::post('/rates', [RateController::class, 'store'])->name('rates.store');
        Route::post('/rates/{rate}/toggle-approve', [RateController::class, 'toggleApprove'])->name('rates.toggle-approve');
        Route::post('/rates/{rate}/toggle-active', [RateController::class, 'toggleActive'])->name('rates.toggle-active');
        Route::post('/rates/{rate}/toggle-lock', [RateController::class, 'toggleLock'])->name('rates.toggle-lock');
        Route::post('/rates/{rate}/update', [RateController::class, 'update'])->name('rates.update.legacy');
        Route::post('/rates/{rate}/delete', [RateController::class, 'destroy'])->name('rates.delete.legacy');
        Route::post('/rates/import', [RateController::class, 'import'])->name('rates.import');
    });

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/generate', [BillingController::class, 'generate'])->middleware('permission:billing.generate')->name('billing.generate');
    Route::post('/billing/{billing}/correct', [BillingController::class, 'correct'])->middleware('permission:billing.correct')->name('billing.correct');
    Route::post('/billing/due-date/bulk', [BillingController::class, 'bulkUpdateDueDate'])->middleware('permission:billing.generate')->name('billing.due-date.bulk');
    Route::post('/billing/{billing}/due-date', [BillingController::class, 'updateDueDate'])->middleware('permission:billing.generate')->name('billing.due-date');
    Route::post('/billing/bulk-rate-correction', [BillingController::class, 'bulkRateCorrection'])->middleware('permission:billing.correct')->name('billing.bulk-rate-correction');
    Route::get('/mess-costing', [MessCostingController::class, 'index'])->name('mess-costing.index');
    Route::post('/mess-costing', [MessCostingController::class, 'store'])->name('mess-costing.store');
    Route::get('/mess-costing/{costing}', [MessCostingController::class, 'show'])->name('mess-costing.show');
    Route::get('/mess-costing/{costing}/print', [MessCostingController::class, 'print'])->name('mess-costing.print');
    Route::get('/mess-costing/{costing}/export', [MessCostingController::class, 'export'])->name('mess-costing.export');

    Route::middleware('permission:payments.view_admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments-v2', [PaymentController::class, 'indexV2'])->name('payments.index.v2');
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'uploadedProof'])->name('payments.proof');
        Route::get('/payments/{payment}/detail', [PaymentController::class, 'detail'])->name('payments.detail');
    });
    Route::middleware('permission:payments.manual_record_admin')->group(function () {
        Route::get('/payments/member-bill-lookup', [PaymentController::class, 'memberBillLookup'])->name('payments.member-bill-lookup');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    });
    Route::middleware('permission:payments.override_status_admin')->group(function () {
        Route::post('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::post('/payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
    });
    Route::middleware('permission:payments.verify_admin')->group(function () {
        Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/approve-uploaded-proof', [PaymentController::class, 'approveUploadedProof'])->name('payments.approve-uploaded-proof');
        Route::post('/payments/{payment}/reject-uploaded-proof', [PaymentController::class, 'rejectUploadedProof'])->name('payments.reject-uploaded-proof');
        Route::post('/payments/transactions/{transaction}/verify', [PaymentController::class, 'verifyTransaction'])->name('payments.transactions.verify');
    });
    Route::middleware('permission:payments.reconcile_admin')->group(function () {
        Route::post('/payments/reconciliations/{reconciliation}/reconcile', [PaymentController::class, 'reconcile'])->name('payments.reconciliations.reconcile');
    });

    Route::middleware('permission:report.view')->group(function () {
        Route::get('/month-governance', [MonthGovernanceController::class, 'index'])->name('month.index');
        Route::get('/summary', [SummaryController::class, 'index'])->name('summary.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/bills-download', [ReportController::class, 'billsDownload'])->name('reports.bills-download');
        Route::get('/reports/bills-download/export.csv', [ReportController::class, 'billsDownloadExportCsv'])->name('reports.bills-download.export.csv');
        Route::get('/reports/bills-download/export.xlsx', [ReportController::class, 'billsDownloadExportXlsx'])->name('reports.bills-download.export.xlsx');
        Route::get('/overall-recovery', [ReportController::class, 'overallRecovery'])->name('reports.overall-recovery');
        Route::get('/statement', [StatementController::class, 'index'])->name('statement.index');
    });
    Route::post('/month-governance/close', [MonthGovernanceController::class, 'close'])->middleware('permission:month.close')->name('month.close');
    Route::post('/month-governance/reopen', [MonthGovernanceController::class, 'reopen'])->middleware('permission:month.reopen')->name('month.reopen');
    Route::post('/month-governance/hard-reset', [MonthGovernanceController::class, 'hardReset'])->middleware('permission:month.reset_hard')->name('month.hard-reset');

    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::post('/ledger/adjustments', [LedgerController::class, 'storeAdjustment'])->middleware('permission:ledger.adjust')->name('ledger.adjustments.store');
    Route::post('/ledger/import', [LedgerToolchainController::class, 'importLedger'])->middleware('permission:ledger.adjust')->name('ledger.import');
    Route::post('/ledger/recompute', [LedgerToolchainController::class, 'recompute'])->middleware('permission:ledger.recompute')->name('ledger.recompute');

    Route::get('/audit-log', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit-log.index');

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/items', [InventoryController::class, 'storeItem'])->name('inventory.items.store');
        Route::post('/inventory/items/{item}/update', [InventoryController::class, 'updateItem'])->name('inventory.items.update');
        Route::post('/inventory/items/bulk-upload', [InventoryController::class, 'bulkUploadItems'])->name('inventory.items.bulk-upload');
        Route::post('/inventory/transactions', [InventoryController::class, 'storeTxn'])->name('inventory.txns.store');
        Route::post('/inventory/vendor-returns', [InventoryController::class, 'storeVendorReturn'])->name('inventory.vendor-returns.store');
        Route::post('/inventory/items/import', [InventoryController::class, 'importItems'])->name('inventory.items.import');
        Route::get('/inventory/stock-ledger/export', [InventoryController::class, 'exportStockLedger'])->name('inventory.stock-ledger.export');
        Route::get('/inventory/items/export', [InventoryController::class, 'exportItems'])->name('inventory.items.export');
        Route::post('/inventory/stock-counts', [InventoryController::class, 'storeStockCount'])->name('inventory.stock-counts.store');
        Route::get('/inventory/stock-counts/{stockCount}', [InventoryController::class, 'showStockCount'])->name('inventory.stock-counts.show');
        Route::post('/inventory/stock-counts/{stockCount}/post', [InventoryController::class, 'postStockCount'])->name('inventory.stock-counts.post');
        Route::get('/inventory/items/{item}/trail', [InventoryController::class, 'trail'])->name('inventory.items.trail');
    });

    Route::middleware('permission:procurement.manage')->group(function () {
        Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
        Route::get('/procurement/vendors', fn () => redirect()->route('admin.procurement.index', ['tab' => 'vendors']));
        Route::get('/procurement/po', fn () => redirect()->route('admin.procurement.index', ['tab' => 'po']));
        Route::get('/procurement/grn', fn () => redirect()->route('admin.procurement.index', ['tab' => 'grn']));
        Route::get('/procurement/reports', fn () => redirect()->route('admin.procurement.index', ['tab' => 'reports']));
        Route::post('/procurement/vendors', [ProcurementController::class, 'storeVendor'])->name('procurement.vendors.store');
        Route::get('/procurement/po/template', [ProcurementController::class, 'downloadPoTemplate'])->name('procurement.po.template');
        Route::post('/procurement/po/import/preview', [ProcurementController::class, 'previewPoImport'])->name('procurement.po.import.preview');
        Route::post('/procurement/po/import/store', [ProcurementController::class, 'storePoImport'])->name('procurement.po.import.store');
        Route::post('/procurement/po', [ProcurementController::class, 'storePo'])->name('procurement.po.store');
        Route::post('/procurement/po/bulk-approve', [ProcurementController::class, 'bulkApprovePo'])->name('procurement.po.bulk-approve');
        Route::post('/procurement/po/{po}/approve', [ProcurementController::class, 'approvePo'])->name('procurement.po.approve');
        Route::post('/procurement/purchase-orders/{po}/cancel', [ProcurementController::class, 'cancelPurchaseOrder'])->name('procurement.po.cancel');
        Route::post('/procurement/purchase-orders/{po}/lines/update', [ProcurementController::class, 'updatePurchaseOrderLines'])->name('procurement.po.lines.update');
        Route::get('/procurement/grn/template', [ProcurementController::class, 'downloadGrnTemplate'])->name('procurement.grn.template');
        Route::post('/procurement/grn/import/preview', [ProcurementController::class, 'previewGrnImport'])->name('procurement.grn.import.preview');
        Route::post('/procurement/grn/import/store', [ProcurementController::class, 'storeGrnImport'])->name('procurement.grn.import.store');
        Route::post('/procurement/po/import/cancel', [ProcurementController::class, 'cancelPoImportPreview'])->name('procurement.po.import.cancel');
        Route::post('/procurement/grn/import/cancel', [ProcurementController::class, 'cancelGrnImportPreview'])->name('procurement.grn.import.cancel');
        Route::post('/procurement/grn', [ProcurementController::class, 'storeGrn'])->name('procurement.grn.store');
        Route::post('/procurement/grn/bulk-approve', [ProcurementController::class, 'bulkApproveGrn'])->name('procurement.grn.bulk-approve');
        Route::post('/procurement/grn/{grn}/approve', [ProcurementController::class, 'approveGrn'])->name('procurement.grn.approve');
        Route::post('/procurement/grn/{grn}/reverse', [ProcurementController::class, 'reverseGrn'])->name('procurement.grn.reverse');
        Route::get('/procurement/reports/export', [ProcurementController::class, 'exportPurchaseReports'])->name('procurement.reports.export');
        Route::get('/procurement/reports/export-selected', [ProcurementController::class, 'exportSelectedPurchaseReport'])->name('procurement.reports.export-selected');
    });
    Route::middleware('permission:report.export')->group(function () {
        Route::get('/procurement/grn/export/detail', [ProcurementController::class, 'exportGrnDetail'])->name('procurement.grn.export.detail');
        Route::get('/procurement/grn/export/summary', [ProcurementController::class, 'exportGrnSummary'])->name('procurement.grn.export.summary');
    });

    Route::middleware('permission:complaint.view_all')->group(function () {
        Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
        Route::get('/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
        Route::get('/member-profile-change-requests', [MemberProfileChangeRequestController::class, 'index'])->name('member-profile-change-requests.index');
    });
    Route::post('/complaints/{complaint}/status', [AdminComplaintController::class, 'updateStatus'])->middleware('permission:complaint.manage')->name('complaints.status');
    Route::post('/member-profile-change-requests/{changeRequest}', [MemberProfileChangeRequestController::class, 'update'])->middleware('permission:complaint.manage')->name('member-profile-change-requests.update');
    Route::get('/complaints/export', [AdminComplaintController::class, 'export'])->middleware('permission:complaint.export')->name('complaints.export');

    Route::middleware('permission:menu.view')->group(function () {
        Route::get('/menu', [AdminMenuController::class, 'index'])->name('menu.index');
    });
    Route::post('/menu', [AdminMenuController::class, 'store'])->middleware('permission:menu.manage')->name('menu.store');
    Route::put('/menu/{menu}', [AdminMenuController::class, 'update'])->middleware('permission:menu.manage')->name('menu.update');
    Route::post('/menu/{menu}/approve', [AdminMenuController::class, 'approve'])->middleware('permission:menu.approve')->name('menu.approve');
    Route::post('/menu/{menu}/inactive', [AdminMenuController::class, 'inactive'])->middleware('permission:menu.approve')->name('menu.inactive');
    Route::get('/menu/export', [AdminMenuController::class, 'export'])->middleware('permission:menu.export')->name('menu.export');

    Route::middleware('permission:kitchen.manage')->group(function () {
        Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('/kitchen/menus', [KitchenController::class, 'storeMenu'])->name('kitchen.menus.store');
        Route::post('/kitchen/menus/{menu}/edit', [KitchenController::class, 'updateMenu'])->name('kitchen.menus.edit.legacy');
        Route::post('/kitchen/menus/{menu}/delete', [KitchenController::class, 'deleteMenu'])->name('kitchen.menus.delete.legacy');
        Route::post('/kitchen/recipes', [KitchenController::class, 'storeRecipe'])->name('kitchen.recipes.store');
        Route::post('/kitchen/recipes/{recipe}/edit', [KitchenController::class, 'updateRecipe'])->name('kitchen.recipes.edit.legacy');
        Route::post('/kitchen/recipes/{recipe}/delete', [KitchenController::class, 'deleteRecipe'])->name('kitchen.recipes.delete.legacy');
        Route::post('/kitchen/plans', [KitchenController::class, 'storePlan'])->name('kitchen.plans.store');
        Route::post('/kitchen/plans/{plan}/edit', [KitchenController::class, 'updatePlan'])->name('kitchen.plans.edit.legacy');
        Route::post('/kitchen/plans/{plan}/approve', [KitchenController::class, 'approvePlan'])->name('kitchen.plans.approve.legacy');
        Route::post('/kitchen/issues', [KitchenController::class, 'issue'])->name('kitchen.issues.store');
        Route::get('/kitchen/ledger/export', [KitchenController::class, 'exportLedgerConsumption'])->name('kitchen.ledger.export');
        Route::get('/kitchen/ledger/export-summary', [KitchenController::class, 'exportLedgerConsumptionSummary'])->name('kitchen.ledger.export-summary');
        Route::get('/kitchen/consumption-report/export', [KitchenController::class, 'exportConsumptionReport'])->name('kitchen.consumption-report.export');
        Route::post('/kitchen/issues/{issue}/approve', [KitchenController::class, 'approveIssue'])->name('kitchen.issues.approve.legacy');
    });

    Route::middleware('permission:guest.manage')->group(function () {
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/guests/print', [GuestController::class, 'printReport'])->name('guests.print');
        Route::post('/guests', [GuestController::class, 'storeGuest'])->name('guests.store');
        Route::post('/guests/{guest}/edit', [GuestController::class, 'updateGuest'])->name('guests.edit.legacy');
        Route::post('/guests/{guest}/delete', [GuestController::class, 'deleteGuest'])->name('guests.delete.legacy');
        Route::post('/guests/meals', [GuestController::class, 'storeMeal'])->name('guests.meals.store');
        Route::post('/guests/meals/{meal}/update', [GuestController::class, 'updateMeal'])->name('guests.meals.update.legacy');
        Route::post('/guests/meals/{meal}/delete', [GuestController::class, 'deleteMeal'])->name('guests.meals.delete.legacy');
        Route::post('/guests/meals/{meal}/approve', [GuestController::class, 'approveMeal'])->name('guests.meals.approve.legacy');
        Route::post('/guests/meals/approve-range', [GuestController::class, 'approveMealRange'])->name('guests.meals.approve-range');
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
        Route::get('/exports/bills', [ExportCenterController::class, 'bills'])->name('exports.bills');
        Route::get('/exports/payments', [ExportCenterController::class, 'payments'])->name('exports.payments');
        Route::get('/exports/member-ledger', [ExportCenterController::class, 'memberLedger'])->name('exports.member-ledger');
        Route::get('/exports/statement', [ExportCenterController::class, 'statement'])->name('exports.statement');
        Route::get('/exports/stock-ledger', [ExportCenterController::class, 'stockLedger'])->name('exports.stock-ledger');
        Route::get('/exports/guest-meals', [ExportCenterController::class, 'guestMeals'])->name('exports.guest-meals');
        Route::get('/exports/department-ledger', [ExportCenterController::class, 'departmentLedger'])->name('exports.department-ledger');
    });

    Route::middleware('permission:settings.dangerous')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
        Route::post('/settings/{setting}/toggle', [SettingController::class, 'toggle'])->name('settings.toggle');
        Route::get('/app-settings', [AdminAppSettingsController::class, 'edit'])->name('app-settings.edit');
        Route::post('/app-settings', [AdminAppSettingsController::class, 'update'])->name('app-settings.update');
    });

    Route::middleware('permission:accounting.manage')->group(function () {
        Route::post('/settings/departments', [SettingController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::post('/settings/departments/{department}/update', [SettingController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::post('/settings/departments/{department}/remove', [SettingController::class, 'removeDepartment'])->name('settings.departments.remove');
        Route::post('/settings/departments/{department}/reactivate', [SettingController::class, 'reactivateDepartment'])->name('settings.departments.reactivate');

        Route::post('/settings/messes', [SettingController::class, 'storeMess'])->name('settings.messes.store');
        Route::post('/settings/messes/{mess}/update', [SettingController::class, 'updateMess'])->name('settings.messes.update');
        Route::post('/settings/messes/{mess}/remove', [SettingController::class, 'removeMess'])->name('settings.messes.remove');
        Route::post('/settings/messes/{mess}/reactivate', [SettingController::class, 'reactivateMess'])->name('settings.messes.reactivate');
    });
});

Route::prefix('member')->name('member.')->middleware(['auth', 'force_password_change', 'active', 'role:MEMBER'])->group(function () {
    Route::post('/device-token', [MemberDeviceTokenController::class, 'store'])->name('device-token.store');
    Route::delete('/device-token', [MemberDeviceTokenController::class, 'destroy'])->name('device-token.destroy');
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::middleware('permission:payments.view_own')->group(function () {
        Route::get('/bill', [MemberPaymentController::class, 'index'])->name('bill');
        Route::get('/payments', [MemberPaymentController::class, 'index'])->name('payments.index');
        Route::get('/statement', [MemberStatementController::class, 'index'])->name('statement.index');
        Route::get('/profile', [MemberProfileController::class, 'index'])->name('profile.index');
    });
    Route::middleware('permission:payments.initiate_own')->group(function () {
        Route::post('/payments/initiate', [MemberPaymentController::class, 'initiate'])->name('payments.initiate');
    });
    Route::middleware('permission:complaint.view_own')->group(function () {
        Route::get('/complaints', [MemberComplaintController::class, 'index'])->name('complaints.index');
    });
    Route::middleware('permission:complaint.submit_own')->group(function () {
        Route::get('/complaints/create', [MemberComplaintController::class, 'create'])->name('complaints.create');
        Route::post('/complaints', [MemberComplaintController::class, 'store'])->name('complaints.store');
        Route::post('/profile/change-requests', [MemberProfileController::class, 'storeChangeRequest'])->name('profile.change-requests.store');
    });
    Route::middleware('permission:complaint.view_own')->group(function () {
        Route::get('/complaints/{complaint}', [MemberComplaintController::class, 'show'])->name('complaints.show');
    });
    Route::middleware('permission:menu.view')->group(function () {
        Route::get('/menu', [MemberMenuController::class, 'index'])->name('menu.index');
    });
    Route::get('/notifications', [MemberNotificationController::class, 'index'])->name('notifications.index');

    Route::prefix('app')->name('app.')->group(function () {
        Route::redirect('/', '/member/app/dashboard')->name('home');
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:payments.view_own')->group(function () {
            Route::get('/bill', [MemberPaymentController::class, 'index'])->name('bill');
            Route::get('/payments', [MemberPaymentController::class, 'index'])->name('payments.index');
            Route::get('/statement', [MemberStatementController::class, 'index'])->middleware('app_feature:statement')->name('statement.index');
            Route::get('/profile', [MemberProfileController::class, 'index'])->name('profile.index');
        });

        Route::middleware('permission:payments.initiate_own')->group(function () {
            Route::post('/payments/initiate', [MemberPaymentController::class, 'initiate'])->name('payments.initiate');
        });

        Route::middleware(['permission:menu.view', 'app_feature:menu'])->group(function () {
            Route::get('/menu', [MemberMenuController::class, 'index'])->name('menu.index');
        });

        Route::middleware(['permission:complaint.view_own', 'app_feature:complaint'])->group(function () {
            Route::get('/complaints', [MemberComplaintController::class, 'index'])->name('complaints.index');
            Route::get('/complaints/{complaint}', [MemberComplaintController::class, 'show'])->name('complaints.show');
        });

        Route::middleware(['permission:complaint.submit_own', 'app_feature:complaint'])->group(function () {
            Route::get('/complaints/create', [MemberComplaintController::class, 'create'])->name('complaints.create');
            Route::post('/complaints', [MemberComplaintController::class, 'store'])->name('complaints.store');
            Route::post('/profile/change-requests', [MemberProfileController::class, 'storeChangeRequest'])->name('profile.change-requests.store');
        });

        Route::get('/notifications', [MemberNotificationController::class, 'index'])->middleware('app_feature:notification')->name('notifications.index');
    });
});

Route::match(['get','post'], '/jazzcash/return', [\App\Http\Controllers\JazzCashController::class, 'return'])->name('jazzcash.return');
Route::post('/jazzcash/ipn', [\App\Http\Controllers\JazzCashController::class, 'ipn'])->name('jazzcash.ipn');
Route::post('/jazzcash/payments/{payment}/status-inquiry', [\App\Http\Controllers\JazzCashController::class, 'statusInquiry'])->middleware(['auth', 'force_password_change', 'active', 'role:SUPER_ADMIN,ADMIN'])->name('jazzcash.status-inquiry');


Route::get('/data-deletion', function () {
    return view('data-deletion');
})->name('data-deletion');
// OPENCLAW UI PREVIEW ROUTES - FRESH5
Route::prefix('admin/ui-preview')->name('admin.ui-preview.')->group(function () {
    Route::view('/login', 'admin.ui-preview.login')->name('login');
    Route::view('/dashboard', 'admin.ui-preview.dashboard')->name('dashboard');
    Route::view('/members', 'admin.ui-preview.members')->name('members');
    Route::view('/members/create', 'admin.ui-preview.member-form')->name('members.create');
    Route::view('/members/profile', 'admin.ui-preview.member-profile')->name('members.profile');
});
