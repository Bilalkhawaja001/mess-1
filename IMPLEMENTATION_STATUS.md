# IMPLEMENTATION_STATUS.md (P0)

## P0 Checklist

- [x] Password recovery flow (`/password-reset/request`, `/password-reset`) + change-password (`/change-password`)
- [x] Month close/reopen/hard reset governance endpoints and service
- [x] Billing correction/reversal lifecycle endpoint and service wiring
- [x] Payment edit workflow with state guard (deny edits once approved)
- [x] Ledger import/opening-balance/recompute toolchain endpoints and service
- [x] `permissions` + `role_permissions` schema and route-level permission middleware
- [x] `audit_logs` table + route/UI (`/admin/audit-log`) with action logging hooks
- [x] `password_reset_tokens` schema/model
- [x] `month_closures` schema/model
- [x] Bills export endpoints (`/admin/reports/bills-download/{format}` for csv/xlsx)

## What changed

### Schema
- Added `create_permissions_and_audit_tables` migration.
- Added `add_p0_workflow_columns` migration:
  - billing reversal/correction columns
  - payment edit metadata columns
  - opening-balance marker on ledger rows

### Models
- Added: `Permission`, `AuditLog`, `PasswordResetToken`, `MonthClosure`
- Extended: `User` permission helper, `Role` permissions relation, `Billing` correction fields, `Payment` edit status fields

### Middleware
- Added `PermissionMiddleware` and kernel alias `permission`.

### Services
- Added thin-domain services:
  - `AuditLogService`
  - `Auth\PasswordResetService`
  - `Auth\PasswordChangeService`
  - `MonthClosureService`
  - `Billing\BillingCorrectionService`
  - `PaymentEditService`
  - `LedgerToolchainService`
  - `BillExportService`

### Controllers/routes/UI
- Added auth reset/change routes + controller methods + blades.
- Added month governance controller and UI.
- Added audit log controller and UI.
- Added billing correction + export handlers.
- Added payment edit action and UI controls.
- Added ledger toolchain endpoints + UI controls.
- Added navigation links for month governance, audit log, change password.

### Seeders/tests
- Added `PermissionSeeder` and wired into `DatabaseSeeder`.
- Added `tests/Feature/P0WorkflowTest.php` baseline coverage stub.
