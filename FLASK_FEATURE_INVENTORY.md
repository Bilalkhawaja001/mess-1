# Flask Feature Inventory (Canonical)

Source: `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow`

## 1) Routes / Endpoints
- Total routes discovered: **126** (`app.py`)
- Method-expanded endpoints: **155**

### Core Auth + Security
- `/login` (GET/POST), `/logout` (POST)
- `/change-password` (GET/POST)
- `/password-reset/request` (GET/POST), `/password-reset` (GET/POST)
- `/csrf-token`, `/whoami`, `/dev-login`
- Health/readiness: `/health`, `/ready`

### Core Billing Operations
- Members: `/members`, bulk upload/sample, edit/deactivate/reactivate/remove
- Attendance: `/attendance`
- Extras: `/extras`
- Billing: `/billing`, billing correction
- Rates: `/rates`, approve/lock/update/delete
- Payments: `/payments`, approve/edit
- Ledger: `/ledger`, `/admin/import-ledger`, opening balances import, recompute

### Inventory + Procurement
- Items: `/items`, csv sample/import, edit
- Vendors: `/vendors`
- Purchase Orders: `/po`, approve
- GRN: `/grn`, approve
- Stock: `/stock-ledger`, `/stock-balance`, `/stock-count`, stock txn + approvals

### Meal Planning + Kitchen
- Menus: `/menus`
- Recipes: `/recipes/<menu_id>`
- Meal plans: `/meal-plans` (+ approve/edit)
- Plan issue / kitchen issue flows
- Consumption report

### Reporting + Downloads
- `/reports`, `/reports/bills-download`, CSV/XLSX exports
- `/statement`, `/summary`
- `/downloads` + payroll/ledger/statement/attendance exports
- `/finance-reports`, `/department-ledger`
- Recovery pages: `/recovery`, `/overall-recovery`, `/member-balances`, `/bill-recovery`

### Organization / Master Data
- `/master-data`
- `/departments`, `/messes`
- `/users`, `/audit-log`
- Admin hubs: `/ops`, `/reports-hub`, `/inventory-hub`, `/meals-hub`, `/admin-hub`
- Settings: `/settings`, `/admin/settings/app`

### Guest + Member Self-Service
- Guests: `/guests`
- Guest meals: `/guest-meals` (+ edit/delete/approve/export)
- Member portal: `/member/dashboard`, `/member/bill`, `/member/attendance`, `/member/payments`, `/member/profile`

## 2) Pages / Templates
- Total templates discovered: **71**
- Key pages include:
  - Admin: dashboard, users, settings, reports, statement, summary, billing, payments, rates
  - Inventory/procurement: items, vendors, po, grn, stock pages
  - Meal stack: menus, recipes, meal_plans, kitchen_issue, consumption_report
  - Guest + department finance: guests, guest_meals, department_ledger, finance_reports
  - Member UI: member_dashboard, member_bill, member_attendance, member_payments, member_profile

## 3) Modules (Domain Areas)
- Authentication and password recovery
- User/role/permission and audit
- Member lifecycle and attendance
- Extras/rates/billing/payment/ledger
- Inventory (items/stock/uom)
- Procurement (vendor/PO/GRN)
- Meal planning + kitchen issuance
- Guests and guest meal billing
- Department/mess accounting
- Reporting/export/download center
- App settings + operational hubs

## 4) Forms / Input Surfaces
- Flask app is largely route-driven form handling in `app.py` (no separate FlaskForm classes detected)
- Heavy POST surfaces for CRUD, approvals, month close/reopen/reset, imports, exports

## 5) Models + DB Tables (from `app.py`)
Detected SQLAlchemy models: **48**

Tables (`__tablename__`):
`users`, `member`, `attendance`, `extra`, `billing`, `billing_runs`, `rate_policy`, `payment`, `member_ledger`, `audit_log`, `permission`, `role_permission`, `password_reset_token`, `app_settings`, `month_closure`, `item`, `uom_conversion`, `stock_txn`, `stock_txn_line`, `stock_balance`, `stock_count`, `stock_count_line`, `vendor`, `po`, `po_line`, `grn`, `grn_line`, `menu`, `recipe_line`, `meal_plan`, `expected_consumption`, `meal_issue_link`, `kitchen_issue`, `kitchen_issue_line`, `department`, `mess`, `mess_monthly_records`, `mess_expense_lines`, `mess_attendance`, `mess_bill_print_admin`, `member_org`, `monthly_attendance`, `guest`, `guest_meal`, `department_ledger`, `coa_accounts`, `journal_voucher`, `journal_lines`.

## 6) Workflows (Observed)
- Month lifecycle: close → reopen → hard reset
- Billing lifecycle: setup rates/attendance/extras → generate bills → correction → payment approval
- Ledger lifecycle: import opening balances/ledger entries → recompute
- Inventory lifecycle: item import → stock movements/count/approval
- Procurement lifecycle: vendor + PO + GRN approval
- Meal lifecycle: menu/recipe → meal plan → issue/consumption
- Guest billing lifecycle: guest + guest meals + approval/export

## 7) Reports / Exports
- Bills summary exports (CSV/XLSX)
- Payroll export
- Ledger/statement/attendance downloads
- Guest meal export (xlsx)
- Finance + recovery report pages

## 8) Roles / Permissions / Settings
- Explicit permission tables (`permission`, `role_permission`)
- User admin + audit log routes
- Settings pages and admin app settings endpoint

## 9) Uploads / Utilities / Billing Logic
- Member bulk upload + sample
- Items CSV import + sample
- Ledger import + opening balance import
- Multiple utility/debug routes (`/debug/db`, `/debug/attendance-summary`, health/ready)
- Billing-critical logic concentrated in monolithic `app.py` handlers + model-level operations
