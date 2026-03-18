# MIGRATION_MAP.md

## Scope
Phase 2 mapping only (no implementation). Source-of-truth: Flask monolith `app.py`; target: Laravel modular MVC.

---

## A) Launch-Critical Mapping (P0 first)

| Flask route/page/function | Flask model/logic touchpoints | Laravel target route | Laravel controller/action | Laravel view/model/service target | Middleware / authorization | Priority | Status |
|---|---|---|---|---|---|---|---|
| `/login` (`login`) | `User`, password hash verify | `/login` | `AuthController@showLoginForm/login` | `auth/login.blade.php`, `User` | `guest` | P0 | mapped |
| `/logout` (`logout`) | session clear | `/logout` | `AuthController@logout` | session/auth layer | `auth` | P0 | mapped |
| `/change-password` (`change_password`) | `User.must_change_password` | `/change-password` | `AuthController@changePassword` | auth service + password form view | `auth,active` | P0 | missing |
| `/password-reset/request` | `PasswordResetToken` create/expire | `/password-reset/request` | `AuthController@requestReset` | token service + reset request view | `guest` | P0 | missing |
| `/password-reset` | token validate + password set | `/password-reset` | `AuthController@resetPassword` | reset form + token consume service | `guest` | P0 | missing |
| `/users` (`users_page`) | `User`, role assignment, audit entries | `/admin/users` | `Admin\\UserController@index/store/update/toggleActive` | admin users blade + `User`, `Role` | `auth,active,role:*` | P0 | mapped (permission/audit parity missing) |
| `/members` + edit/deactivate/reactivate | `Member`, referential checks | `/admin/members` | `Admin\\MemberController@index/store/update/toggleActive` | members blade + `Member` | `auth,active,role:*` | P0 | mapped |
| `/attendance` | `Attendance`, day-wise meals | `/admin/attendance` | `Admin\\AttendanceController@index/store` | attendance blade + `Attendance` | `auth,active,role:*` | P0 | mapped |
| `/attendance-monthly` | `MonthlyAttendance` materialization/lock | `/admin/attendance-monthly` | `Admin\\MonthlyAttendanceController@index/store` | monthly attendance blade + `MonthlyAttendance` | `auth,active,role:*` | P0 | mapped (approve/unlock missing) |
| `/extras` | `Extra` | `/admin/extras` | `Admin\\ExtraController@index/store` | extras blade + `Extra` | `auth,active,role:*` | P0 | mapped |
| `/rates` + approve | `RatePolicy` effective windows | `/admin/rates` + `/toggle-approve` | `Admin\\RateController@index/store/toggleApprove` | rates blade + `RatePolicy` | `auth,active,role:*` | P0 | mapped |
| `/billing` + generate | `Billing`, `BillingRun`, calculation logic | `/admin/billing` + `/billing/generate` | `Admin\\BillingController@index/generate` | billing blade + billing service | `auth,active,role:*` | P0 | mapped |
| `/billing/<bill_id>/correct` | bill adjustment + reversal trail | `/admin/billing/{bill}/correct` | `Admin\\BillingController@correct` | correction service, audit logging | `auth,active,role:SUPER_ADMIN,ADMIN` | P0 | missing |
| `/payments` + approve | `Payment` draft/approve states | `/admin/payments` + `/approve` | `Admin\\PaymentController@index/store/approve` | payments blade + `Payment` | `auth,active,role:*` | P0 | mapped |
| `/payments/<payment_id>/edit` | editable draft/approved controls | `/admin/payments/{payment}` | `Admin\\PaymentController@update` | payment update validation | `auth,active,role:SUPER_ADMIN,ADMIN` | P0 | missing |
| `/ledger` + adjustments | `MemberLedger` | `/admin/ledger` + `/ledger/adjustments` | `Admin\\LedgerController@index/storeAdjustment` | ledger blade + `MemberLedger` | `auth,active,role:*` | P0 | mapped |
| `/admin/import-ledger` | CSV upload parse/posting | `/admin/import-ledger` | `Admin\\LedgerImportController@index/importLedger` | import UI + parser service | `auth,active,role:SUPER_ADMIN` | P0 | missing |
| `/admin/import-opening-balances` | opening balance journalization | `/admin/import-opening-balances` | `Admin\\LedgerImportController@importOpeningBalances` | import service + validations | `auth,active,role:SUPER_ADMIN` | P0 | missing |
| `/admin/recompute-ledger` | rebuild member ledger chain | `/admin/recompute-ledger` | `Admin\\LedgerImportController@recompute` | recompute job/service | `auth,active,role:SUPER_ADMIN` | P0 | missing |
| `/month-close` | `MonthClosure`, lock gates | `/admin/month-close` | `Admin\\MonthLifecycleController@close` | month lifecycle service | `auth,active,role:SUPER_ADMIN,ADMIN` | P0 | missing |
| `/month-reopen` | closure rollback gates | `/admin/month-reopen` | `Admin\\MonthLifecycleController@reopen` | month lifecycle service | `auth,active,role:SUPER_ADMIN,ADMIN` | P0 | missing |
| `/month-reset-hard` | destructive reset + audit | `/admin/month-reset-hard` | `Admin\\MonthLifecycleController@hardReset` | guarded reset service | `auth,active,role:SUPER_ADMIN` | P0 | missing |
| `/summary` | aggregated KPIs | `/admin/summary` | `Admin\\SummaryController@index` | summary blade | `auth,active,role:*` | P0 | mapped |
| `/reports` | report index | `/admin/reports` | `Admin\\ReportController@index` | reports blade | `auth,active,role:*` | P0 | mapped |
| `/statement` | member statement | `/admin/statement` | `Admin\\StatementController@index` | statement blade | `auth,active,role:*` | P0 | mapped |
| `/reports/bills-download*` | CSV/XLSX exports | `/admin/reports/bills-download*` | `Admin\\ReportExportController@...` | export service | `auth,active,role:SUPER_ADMIN,ADMIN,AUDITOR` | P0 | missing |
| `/audit-log` | `AuditLog` search/filter | `/admin/audit-log` | `Admin\\AuditLogController@index` | audit log blade + `AuditLog` | `auth,active,role:SUPER_ADMIN,AUDITOR` | P0 | missing |
| `/settings` + `/admin/settings/app` | `AppSetting` | `/admin/settings` | `Admin\\SettingController@index/store/toggle` | settings blade + `AppSetting` | `auth,active,role:SUPER_ADMIN` | P0 | mapped (richer app settings missing) |

---

## B) Full-Parity Mapping (Post-launch / non-critical to launch)

### Inventory + Procurement (P2)
- Flask: `/items`, `/vendors`, `/po`, `/grn`, `/stock-ledger`, `/stock-balance`, `/stock-count`, `/stock-txn`
- Laravel target namespace: `/admin/inventory/*`, `/admin/procurement/*`
- Target modules:
  - Controllers: `ItemController`, `VendorController`, `PurchaseOrderController`, `GrnController`, `StockController`
  - Models: `Item`, `UomConversion`, `StockTxn`, `StockTxnLine`, `StockBalance`, `StockCount`, `StockCountLine`, `Vendor`, `Po`, `PoLine`, `Grn`, `GrnLine`
  - Services: stock valuation/posting, approval workflow services
  - Middleware: `auth, active, role + granular permission:*`

### Meal Planning + Kitchen (P2)
- Flask: `/menus`, `/recipes/<menu_id>`, `/meal-plans`, `/plan-link-issue`, `/kitchen-issue`, `/consumption-report`
- Laravel target: `/admin/meals/*`
- Target models/services: `Menu`, `RecipeLine`, `MealPlan`, `ExpectedConsumption`, `KitchenIssue`, `KitchenIssueLine`, consumption calc service

### Guest Billing (P2)
- Flask: `/guests`, `/guest-meals`, `/guest-meals/export.xlsx`
- Laravel target: `/admin/guests/*`, `/admin/guest-meals/*`
- Target models/services: `Guest`, `GuestMeal`, approval/export service

### Department/Mess Accounting (P2)
- Flask: `/departments`, `/messes`, `/department-ledger`, `/finance-reports`
- Laravel target: `/admin/departments/*`, `/admin/messes/*`, `/admin/department-ledger`
- Target models/services: `Department`, `Mess`, `MessMonthlyRecords`, `MessExpenseLines`, `DepartmentLedger`, COA/JV services

### Member self-service completion (P1)
- Flask: `/member/bill`, `/member/attendance`, `/member/payments`, `/member/profile`
- Laravel target: `/member/*` route set with dedicated controllers/views and policy guards.

---

## C) Flask `app.py` Function-Level Capability Decomposition (Mapped Buckets)

1. **Identity & Access Functions**: `login`, `logout`, `change_password`, `password_reset_request`, `password_reset`, `whoami`, role checks.
2. **Month Governance Functions**: `month_close`, `month_reopen`, `month_reset_hard`.
3. **Core Master Data Functions**: `users_page`, `members`, `edit_member`, `deactivate_member`, `reactivate_member`, `remove_member`, `members_bulk_upload`.
4. **Billing Input Functions**: `attendance`, `attendance_monthly_page`, `extras`, `rates`, `rate_toggle_approve`, `rate_toggle_lock`, `rate_update`.
5. **Billing Engine Functions**: `billing`, `correct_billing_entry`, run hashing/locking logic.
6. **Payment/Ledger Functions**: `payments`, `approve_payment`, `edit_payment`, `ledger_view`, `import_ledger_page`, `import_opening_balances`, `admin_recompute_ledger`.
7. **Reporting/Export Functions**: `summary`, `reports`, `statement`, payroll/bill download endpoints.
8. **Security & Audit Functions**: `audit_log`, `debug_db` (admin-only), action logging helpers.
9. **Inventory/Procurement Functions**: `items`, `vendors`, `po_page`, `approve_po`, `grn_page`, `approve_grn`, stock handlers.
10. **Meal/Kitchen Functions**: `menus_page`, `recipes_page`, `meal_plans_page`, `kitchen_issue_page`, `consumption_report_page`.
11. **Guest/Department Functions**: `guests_page`, `guest_meals_page`, `department_ledger_page`, `finance_reports_page`, department/mess administration.

---

## D) Permission + Middleware Mapping Standard

- Flask decorators (`login_required`, `super_admin_required`, `role_check`) map to Laravel middleware stack:
  - `auth`
  - `active`
  - `role:*`
  - `permission:*` (missing, must be added for parity)
- Every P0 state-changing action requires audit log write (`actor`, `action`, `entity`, `before/after`, `timestamp`).
