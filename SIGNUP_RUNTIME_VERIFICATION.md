# SIGNUP_RUNTIME_VERIFICATION

Date: 2026-03-18 (Asia/Karachi)
Repo: `C:\Users\Bilal\clawd\mess_billing_laravel_app`
Scope: Member OTP registration + Super Admin member account flow only

## Environment + prerequisite execution
- PHP binary used: `C:\php-8.5.4-nts-Win32-vs17-x64\php.exe`
- Initial blocker: `could not find driver (sqlite)` on first `artisan migrate/db:seed` run.
- Runtime fix applied (environment-level only): enabled `extension=pdo_sqlite` and `extension=sqlite3` in `C:\php-8.5.4-nts-Win32-vs17-x64\php.ini`.
- Re-run results:
  - `php artisan migrate --force` ✅ PASS (new migrations executed including OTP/payment scope)
  - `php artisan db:seed --force` ✅ PASS (RoleSeeder, PermissionSeeder, PaymentMethodSeeder)
  - `php artisan route:list` ✅ PASS (registration + member-account routes present)

## UAT Check 1: OTP registration flow
Status: ✅ PASS

Evidence (from `new_scope_runtime_evidence.json`):
- Test member created: `member_id=3`, code `OTP-scope20260318092958`
- OTP row delta: `0 -> 1` (`otp_id=1`)
- Wrong OTP verify attempt result: `false` (expected)
- OTP attempts incremented: `attempts_after_wrong=1`
- Registration completion effect: member linked to user `member_user_id_after_complete=7`

Audit evidence:
- `member.registration.otp.sent`
- `member.registration.otp.verify.failed_wrong`

## UAT Check 2: Super Admin member account flow
Status: ✅ PASS

Evidence:
- Test member created: `member_id=4`, account linked to `user_id=8`
- Deactivate state observed:
  - `user_active=false`
  - `portal_enabled=false`
- Post reset/reactivation observed:
  - `user_active=true`
  - `must_change_password=true`
  - `portal_enabled=true`
- OTP unlock operation affected rows: `otp_unlock_rows=1`

DB effects:
- OTP rows for admin-flow member: `1`
- Unlocked OTP rows for admin-flow member: `1`

## Permissions verification (signup-related)
Status: ✅ PASS (behavior verified)

Observed outcomes:
- Super Admin has payment reconcile permission: `true`
- Member has own payment initiation permission: `true`
- Member has admin payment view permission: `false` (correctly denied)
- Admin has `superadmin.member_account_create`: `true` (configured by current seeder policy)

Note:
- Last line indicates current permission model grants `superadmin.member_account_create` to ADMIN as well (not a runtime break; policy/config decision).

## Audit log verification (signup-related)
Status: ✅ PASS

Observed global audit delta during verification run:
- `before=11`, `after=23`, `delta=+12`

Signup-related action entries were present in inserted set.

## Failures in signup scope
- Functional failures: **None**
- Non-functional blocker handled: missing sqlite driver on first run (resolved via PHP extension enablement)
