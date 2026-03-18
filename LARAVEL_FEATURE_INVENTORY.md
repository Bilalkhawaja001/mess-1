# Laravel Feature Inventory (Current State)

Target: `C:\Users\Bilal\clawd\mess_billing_laravel_app`

## 1) Routes / Endpoints
- Total routes discovered: **37** (from `routes/web.php`)

### Implemented Areas
- Auth: `/login` GET/POST, `/logout` POST
- Admin dashboard: `/dashboard`
- Users: list/create/update/toggle-active
- Members: list/create/update/toggle-active
- Attendance: daily + monthly (`/attendance`, `/attendance-monthly`)
- Extras: list/create
- Rates: list/create/toggle-approve/toggle-active
- Billing: list + generate (`/billing`, `/billing/generate`)
- Payments: list/create/approve
- Ledger: list + adjustment
- Summary, Reports, Statement
- Settings: list/create/toggle
- Member dashboard controller exists, but route collision indicates incomplete member portal routing

## 2) Pages / Views
- Total blade files discovered: **20**

Primary pages:
- `resources/views/auth/login.blade.php`
- Admin: dashboard, attendance, attendance_monthly, billing, extras, ledger, members, payments, rates, reports, settings, statement, summary, users
- Member: `member/dashboard.blade.php`
- Shared layout/partials: app layout, flash, sidebar, topbar

## 3) Modules (Code Structure)
### Controllers
- `AuthController`
- Admin controllers: Attendance, MonthlyAttendance, Billing, Dashboard, Extra, Ledger, Member, Payment, Rate, Report, Setting, Statement, Summary, User
- Member: DashboardController

### Models
Detected Eloquent models: **13**
- `User`, `Role`, `Member`, `Attendance`, `MonthlyAttendance`, `Extra`, `RatePolicy`, `Billing`, `BillingRun`, `BillingCycle`, `Payment`, `MemberLedger`, `AppSetting`

### Form Requests
Detected request validators: **13**
- Attendance, Billing, Extras, Ledger, Members, Payments, Rates, Settings, Summary, Users

## 4) DB Tables (from migrations)
Detected created tables: **13**
- `users`, `roles`, `members`, `attendances`, `monthly_attendances`, `extras`, `rate_policies`, `billings`, `billing_runs`, `billing_cycles`, `payments`, `member_ledgers`, `app_settings`

## 5) Workflows (Implemented Scope)
- Core billing flow (attendance/extras/rates/billing/payments/ledger)
- Basic user/member management
- Basic reporting pages and settings

## 6) Gaps by Architecture
- No dedicated inventory/procurement module (items/vendors/PO/GRN/stock)
- No meal planning / recipe / kitchen issue modules
- No guest management module
- No downloads/export center parity
- No department/mess accounting module
- No month close/reopen/reset endpoints equivalent to Flask
- No explicit permission mapping tables in schema equivalent to Flask `permission` + `role_permission`
- No audit log surface equivalent to Flask audit endpoint
