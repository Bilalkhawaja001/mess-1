# 00 BASELINE AUDIT

- Branch at audit start: `repair/full-parity-fix`
- Baseline commit SHA before repair edits: `52dcf00f6bd0c86d30a7f36f5bc5b585ed4f15f3`
- PHP requirement (composer): `^8.2`
- Laravel requirement (composer): `^12.0`
- Runtime PHP used for repair: `PHP 8.4.4`
- Truth-pack path used as canonical reference: `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow`
- Environment bootstrap required before audit: `composer install`, `.env` creation, `php artisan key:generate`, `php artisan migrate:fresh --seed --force`

## Critical route baseline (before repair evidence capture)

The repo already exposed route surfaces for the major workflows, but baseline code review showed route presence was overstating backend completeness.

Critical route families present before repair:
- `admin.billing.index`
- `admin.billing.generate`
- `admin.billing.correct`
- `admin.payments.index`
- `admin.payments.store`
- `admin.payments.approve`
- `admin.payments.transactions.verify`
- `admin.payments.reconciliations.reconcile`
- `admin.attendance-monthly.index`
- `admin.attendance-monthly.store`
- `admin.attendance-monthly.approve`
- `admin.attendance-monthly.unlock`
- `admin.attendance-monthly.export`
- `admin.month.index`
- `admin.month.close`
- `admin.month.reopen`
- `admin.month.hard-reset`
- `admin.guests.index`
- `admin.guests.meals.approve.legacy`
- `admin.reports.index`
- `admin.reports.overall-recovery`
- `admin.ledger.index`
- `admin.ledger.import`
- `admin.ledger.recompute`
- `admin.summary.index`
- `admin.dashboard`

## Baseline controllers/services/models inspected as critical

### Controllers
- `app/Http/Controllers/Admin/BillingController.php`
- `app/Http/Controllers/Admin/PaymentController.php`
- `app/Http/Controllers/Admin/MonthGovernanceController.php`
- `app/Http/Controllers/Admin/MonthlyAttendanceController.php`
- `app/Http/Controllers/Admin/GuestController.php`
- `app/Http/Controllers/Admin/ReportController.php`
- `app/Http/Controllers/Admin/StatementController.php`
- `app/Http/Controllers/Admin/SummaryController.php`
- `app/Http/Controllers/Admin/ExportCenterController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/KitchenController.php`
- `app/Http/Controllers/Admin/ProcurementController.php`
- `app/Http/Controllers/Admin/AccountingController.php`

### Services
- `app/Services/Billing/BillingGenerationService.php`
- `app/Services/Billing/BillingCorrectionService.php`
- `app/Services/MonthClosureService.php`
- `app/Services/Payments/PaymentService.php`
- `app/Services/Payments/PaymentAttemptService.php`
- `app/Services/Payments/PaymentTransactionService.php`
- `app/Services/Payments/PaymentReconciliationService.php`
- `app/Services/PaymentEditService.php`
- `app/Services/LedgerToolchainService.php`

### Models
- `app/Models/Billing.php`
- `app/Models/BillingCycle.php`
- `app/Models/BillingRun.php`
- `app/Models/MonthlyAttendance.php`
- `app/Models/Attendance.php`
- `app/Models/MemberLedger.php`
- `app/Models/Payment.php`
- `app/Models/PaymentAttempt.php`
- `app/Models/PaymentTransaction.php`
- `app/Models/PaymentReconciliation.php`
- `app/Models/GuestMeal.php`
- `app/Models/DepartmentLedger.php`

## Baseline truth findings before repair edits

### A. Navigation / sidebar / workspaces
- Routes existed for billing, payments, attendance, reports, inventory, kitchen, guests, accounting, and exports.
- Route/view presence did not prove backend truth. Multiple flows were workflow shells only.
- Member portal scope remained limited to dashboard + payment initiation/view; no proof of broader parity found from Laravel code.

### B. Billing engine
Baseline code in `BillingGenerationService` was materially incomplete versus truth requirements:
- generation entry point existed
- month governance was **not enforced** against closed month state
- monthly attendance was **not primary**; service always used daily attendance count
- daily fallback existed, but was the only path
- join/leave windows were partially respected at member inclusion level, but billed days were **not clamped** to employment window
- active approved rate selection existed for `PER_DAY`, but no requirement proof for org/department dependency was implemented
- extras inclusion existed
- member ledger posting existed
- department ledger / journal posting for normal billing was **absent**
- rerun/idempotency used `BillingRun.scope_hash`, but config hash omitted monthly attendance snapshot, so approved monthly-attendance changes would not affect rerun semantics truthfully
- month open/closed enforcement was **absent**

### C. Billing correction
Baseline code in `BillingCorrectionService` only overwrote `billings.net_payable` and logged audit.
- old financial effect reversal: **absent**
- new financial repost: **absent**
- member ledger truth repair: **absent**
- department/journal truth repair: **absent**
- audit trail existed only at model overwrite level

### D. Month governance
Baseline code in `MonthClosureService`:
- close/reopen toggled billing lock and month closure row
- hard reset deleted `billings` only
- hard reset left stale `member_ledgers` and `billing_runs` behind
- `billing_cycles` closure flags were not kept aligned with month governance

### E. Payments
Baseline code in `PaymentController` + payment services:
- create/initiate existed
- verification/approve could mutate ledger more than once if route called repeatedly under certain status shapes
- reconciliation architecture existed, but ledger truth was split between controller and reconciliation states
- approve path posted ledger in controller instead of a fully guarded financial posting layer
- reporting code still used legacy `APPROVED` status in some places instead of actual payment lifecycle states

### F. Ledger / statement / recovery / reports / summary / exports
- statements and summaries read from ledger/billing tables and export paths existed
- overall recovery was based on member ledger debit/credit totals, which is directionally correct
- reports index still used legacy `APPROVED` payment status, so recovery rows for new payment architecture were not truthful
- export center existed for stock ledger, guest meals, and department ledger

### G. Dashboard
Baseline dashboard only returned:
- users
- members
- open billing cycles

It did **not** expose pending payments, collections, billable totals, outstanding totals, recent billing cycles, or recent activity.

### H. Inventory / procurement / kitchen / guests
- procurement store/approve flows existed and posted stock transactions for GRN
- kitchen issue posted stock movement, but approval endpoint was only `touch()` workflow shell
- meal plan approval endpoint was only `touch()` workflow shell
- guest meal approval endpoint was only `touch()` workflow shell and created **no department chargeback truth**

### I. Special subsystem
- `department_ledgers`, `departments`, `messes`, and accounting view/controller existed
- no separate proven Flask “special costing / mess costing bill system” implementation beyond generic department ledger/accounting surface was found in Laravel baseline
- parity blocker remains dependent on stronger truth-pack evidence than route presence alone

## Baseline conclusion
The repo already contained a substantial parity scaffold, but critical financial truth was still incomplete in P0 areas:
1. billing generation governance + monthly attendance truth
2. billing correction financial repair
3. month hard reset ledger/run cleanup
4. payment exact-once posting semantics
5. guest department chargeback posting
