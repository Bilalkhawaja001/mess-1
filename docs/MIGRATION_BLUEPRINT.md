# Flask -> Laravel Migration Blueprint (Phase 0 Audit)

## 1) Findings

### Source audited (read-only)
- `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow\app.py`
- `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow\route_inventory.csv`
- `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow\app_map.md`
- `C:\Users\Bilal\clawd\mess_billing_mvp_phase6_ui_workflow\templates\*.html`

### Architecture snapshot
- Monolithic Flask app (`create_app`) with SQLAlchemy models and route handlers in single `app.py`.
- Auth/session with role guards (`SUPER_ADMIN`, `ADMIN`, `DATA_ENTRY`, `AUDITOR`, `MEMBER`).
- Heavy business workflows:
  - billing generation + idempotency (`billing_runs`)
  - monthly lock/reopen/reset controls
  - payments with approve/posting
  - member and department ledgers
  - attendance daily + monthly
  - rates policy windows + overlap rules
- UI already modernized with shared layout patterns and member/admin shells.

### Route coverage (core scope)
- Auth: `/login`, `/logout`, `/change-password`, `/password-reset*`
- Admin modules: users, members, attendance, attendance-monthly, extras, billing, payments, ledger, summary, reports, statement, rates, settings
- Member portal: `/member/dashboard`, `/member/bill`, `/member/attendance`, `/member/payments`, `/member/profile`

### Data model footprint (tables detected)
`users, member, attendance, extra, billing, billing_runs, rate_policy, payment, member_ledger, audit_log, permission, role_permission, password_reset_token, app_settings, month_closure, member_org, monthly_attendance, department, mess, mess_monthly_records, mess_expense_lines, mess_attendance, mess_bill_print_admin, guest, guest_meal, department_ledger, coa_accounts, journal_voucher, journal_lines` (+ inventory/procurement tables).

---

## 2) Feature Mapping (Flask -> Laravel)

| Flask route/page | Purpose | Laravel equivalent |
|---|---|---|
| `/login`, `/logout` | Auth login/logout | `AuthController@login/logout`, `routes/web.php` guest/auth middleware |
| `/change-password` | Password change policy | `AuthController@changePassword` + FormRequest |
| `/users` | User CRUD/activation/roles | `Admin\\UserController@index/store/update/toggle` + `resources/views/admin/users/index.blade.php` |
| `/members` | Member master + bulk upload | `Admin\\MemberController` + import action service |
| `/attendance` | Daily attendance entry | `Admin\\AttendanceController@daily` |
| `/attendance-monthly` | Monthly attendance post/approve/unlock | `Admin\\MonthlyAttendanceController` |
| `/extras` | Extra charges | `Admin\\ExtraController` |
| `/billing` | Billing generation + lock | `Admin\\BillingController@index/generate` + `BillingService` |
| `/payments` + approve | Payment draft + approval posting | `Admin\\PaymentController` + `LedgerPostingService` |
| `/ledger` | Member ledger view/filter | `Admin\\LedgerController` |
| `/summary` | Month/member summary | `Admin\\SummaryController` |
| `/reports` | Reports hub | `Admin\\ReportController@index` |
| `/statement` | Statement/print | `Admin\\StatementController@show/print` |
| `/rates` | Rate policy lifecycle | `Admin\\RatePolicyController` |
| `/settings` | Members/departments/messes/rates tabs | `Admin\\SettingsController` |
| `/member/dashboard` | Member dashboard | `Member\\DashboardController@index` |
| `/member/bill` | Member bill detail | `Member\\BillController@index/show` |
| `/member/attendance` | Member attendance view | `Member\\AttendanceController@index` |
| `/member/payments` | Member payment history | `Member\\PaymentController@index` |
| `/member/profile` | Member profile | `Member\\ProfileController@index/update` |

---

## 3) DB Migration Plan (MySQL, Laravel migrations)

### Core migration batches
1. **Security/Auth**: `users`, `password_reset_tokens`, `permissions`, `role_permissions`
2. **Master Data**: `members`, `member_org`, `departments`, `messes`, `app_settings`
3. **Attendance**: `attendance`, `monthly_attendance`, `month_closure`
4. **Billing & Finance**: `rate_policies`, `billing_runs`, `billings`, `payments`, `member_ledgers`, `department_ledgers`
5. **Reporting/Audit**: `audit_logs`, `mess_monthly_records`, `mess_expense_lines`, `mess_attendance`, `mess_bill_print_admin`
6. **Optional phase** (after required scope): inventory/procurement tables

### Field mapping rules
- Flask `member` -> Laravel table `members`
- Flask `extra` -> Laravel `extras`
- Flask `billing` -> Laravel `billings`
- Keep all business identifiers and statuses equivalent.
- Convert numeric precision from float to `decimal(x,y)` where finance-critical.

---

## 4) Files/Folders created in Laravel workspace

Root: `C:\Users\Bilal\clawd\mess_billing_laravel_app`

Created:
- `README.md`
- `.env.example`
- `routes/web.php`
- `docs/MIGRATION_BLUEPRINT.md`
- `app/Http/Controllers/Admin/`
- `app/Http/Controllers/Member/`
- `app/Services/Billing/`
- `app/Services/Attendance/`
- `app/Models/`
- `database/migrations/`
- `database/seeders/`
- `resources/views/layouts/`
- `resources/views/partials/`
- `resources/views/admin/`
- `resources/views/member/`

---

## 5) Exact implementation summary (what done right now)

- Live Flask project untouched (read-only audit).
- Full migration blueprint documented.
- Isolated Laravel target workspace created in new folder.
- Base structure aligned for shared-hosting-compatible Laravel app.
- Ready for Phase 1 implementation: auth + shared layout shell + first migrations.

---

## 6) Verification steps

1. Confirm source untouched:
   - check no file writes under `...\mess_billing_mvp_phase6_ui_workflow`
2. Confirm new workspace exists:
   - `C:\Users\Bilal\clawd\mess_billing_laravel_app`
3. Confirm blueprint file exists:
   - `docs/MIGRATION_BLUEPRINT.md`
4. Confirm route/env placeholders exist:
   - `routes/web.php`, `.env.example`

---

## 7) Risks / blockers / missing assumptions

1. **PHP/Composer missing on this machine currently** (`php` and `composer` not found in PATH), so `composer create-project laravel/laravel` could not be executed yet.
2. Need decision for auth package:
   - Laravel Breeze (simple) vs custom auth (closer to legacy roles).
3. Need final DB cutover strategy:
   - migrate existing data from Flask DB directly vs fresh Laravel DB + import scripts.
4. Scope edge: current Flask includes extra inventory/procurement modules not in mandatory list; confirm include/exclude in v1.

---

## 8) Progress

- Audit + blueprint: **20%**
- Isolated Laravel structure scaffold: **25%**
- Pending next: migrations + auth + layout shell + module-by-module port.
