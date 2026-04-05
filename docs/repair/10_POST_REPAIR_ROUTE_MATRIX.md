# 10 POST-REPAIR ROUTE MATRIX

## Critical routes after repair

| Route name | Method/URI | Controller | Service / truth path | Status after repair |
|---|---|---|---|---|
| `admin.dashboard` | `GET admin/dashboard` | `Admin\DashboardController@index` | query-backed stats from `users`, `members`, `billing_cycles`, `payments`, `member_ledgers`, `billings` | Repaired for real metrics |
| `admin.billing.index` | `GET admin/billing` | `Admin\BillingController@index` | `Billing` listing | Existing surface retained |
| `admin.billing.generate` | `POST admin/billing/generate` | `Admin\BillingController@generate` | `BillingGenerationService::generate()` | Repaired for governance + monthly attendance + idempotency hash |
| `admin.billing.correct` | `POST admin/billing/{billing}/correct` | `Admin\BillingController@correct` | `BillingCorrectionService::correct()` | Repaired for ledger delta truth |
| `admin.payments.index` | `GET admin/payments` | `Admin\PaymentController@index` | `Payment`, `PaymentTransaction`, `PaymentReconciliation` | Existing surface retained |
| `admin.payments.store` | `POST admin/payments` | `Admin\PaymentController@store` | `PaymentAttemptService`, `PaymentTransactionService` | Existing initiation flow retained |
| `admin.payments.approve` | `POST admin/payments/{payment}/approve` | `Admin\PaymentController@approve` | guarded approve + exact-once `MemberLedger` post | Repaired |
| `admin.payments.transactions.verify` | `POST admin/payments/transactions/{transaction}/verify` | `Admin\PaymentController@verifyTransaction` | `PaymentTransactionService::manualVerify()` | Existing surface retained |
| `admin.payments.reconciliations.reconcile` | `POST admin/payments/reconciliations/{reconciliation}/reconcile` | `Admin\PaymentController@reconcile` | `PaymentReconciliationService::reconcile()` | Existing surface retained |
| `admin.attendance-monthly.index` | `GET admin/attendance-monthly` | `Admin\MonthlyAttendanceController@index` | `MonthlyAttendance` + daily attendance fallback | Verified |
| `admin.attendance-monthly.store` | `POST admin/attendance-monthly` | `Admin\MonthlyAttendanceController@store` | save snapshot rows | Verified |
| `admin.attendance-monthly.approve` | `POST admin/attendance-monthly/approve` | `Admin\MonthlyAttendanceController@approve` | lock/approve snapshot rows | Verified |
| `admin.attendance-monthly.unlock` | `POST admin/attendance-monthly/unlock` | `Admin\MonthlyAttendanceController@unlock` | unlock snapshot rows | Verified |
| `admin.attendance-monthly.export` | `GET admin/attendance-monthly/export` | `Admin\MonthlyAttendanceController@export` | CSV export from `monthly_attendances` | Verified |
| `admin.month.index` | `GET admin/month-governance` | `Admin\MonthGovernanceController@index` | `MonthClosure` list | Existing surface retained |
| `admin.month.close` | `POST admin/month-governance/close` | `Admin\MonthGovernanceController@close` | `MonthClosureService::close()` | Repaired to sync `billing_cycles` |
| `admin.month.reopen` | `POST admin/month-governance/reopen` | `Admin\MonthGovernanceController@reopen` | `MonthClosureService::reopen()` | Repaired to sync `billing_cycles` |
| `admin.month.hard-reset` | `POST admin/month-governance/hard-reset` | `Admin\MonthGovernanceController@hardReset` | `MonthClosureService::hardReset()` | Repaired to remove billing ledgers + runs |
| `admin.ledger.index` | `GET admin/ledger` | `Admin\LedgerController@index` | member ledger source of truth | Existing surface retained |
| `admin.ledger.import` | `POST admin/ledger/import` | `Admin\LedgerToolchainController@importLedger` | `LedgerToolchainService::importOpeningBalances()` | Verified |
| `admin.ledger.recompute` | `POST admin/ledger/recompute` | `Admin\LedgerToolchainController@recompute` | `LedgerToolchainService::recompute()` | Verified |
| `admin.reports.index` | `GET admin/reports` | `Admin\ReportController@index` | billing/payment/ledger aggregation | Still contains legacy payment-status assumption gap |
| `admin.reports.overall-recovery` | `GET admin/overall-recovery` | `Admin\ReportController@overallRecovery` | `member_ledgers` debit/credit aggregation | Verified |
| `admin.summary.index` | `GET admin/summary` | `Admin\SummaryController@index` | `Billing` source with CSV/XLSX export | Verified |
| `admin.guests.index` | `GET admin/guests` | `Admin\GuestController@index` | `Guest` / `GuestMeal` | Existing surface retained |
| `admin.guests.meals.approve.legacy` | `POST admin/guests/meals/{meal}/approve` | `Admin\GuestController@approveMeal` | `DepartmentLedger::updateOrCreate()` chargeback | Repaired |

## Route proof commands executed

```powershell
php artisan route:list --name=admin.billing.index
php artisan route:list --name=admin.billing.generate
php artisan route:list --name=admin.billing.correct
php artisan route:list --name=admin.payments.index
php artisan route:list --name=admin.payments.store
php artisan route:list --name=admin.payments.approve
php artisan route:list --name=admin.payments.transactions.verify
php artisan route:list --name=admin.payments.reconciliations.reconcile
php artisan route:list --name=admin.attendance-monthly.index
php artisan route:list --name=admin.attendance-monthly.store
php artisan route:list --name=admin.attendance-monthly.approve
php artisan route:list --name=admin.attendance-monthly.unlock
php artisan route:list --name=admin.attendance-monthly.export
php artisan route:list --name=admin.month.index
php artisan route:list --name=admin.month.close
php artisan route:list --name=admin.month.reopen
php artisan route:list --name=admin.month.hard-reset
php artisan route:list --name=admin.guests.index
php artisan route:list --name=admin.guests.meals.approve.legacy
php artisan route:list --name=admin.reports.index
php artisan route:list --name=admin.reports.overall-recovery
php artisan route:list --name=admin.ledger.index
php artisan route:list --name=admin.ledger.import
php artisan route:list --name=admin.ledger.recompute
php artisan route:list --name=admin.summary.index
php artisan route:list --name=admin.dashboard
```
