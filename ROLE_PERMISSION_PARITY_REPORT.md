# ROLE_PERMISSION_PARITY_REPORT

Date: 2026-03-18

Canonical source: Flask role checks + permission model expectations (`permission`, `role_permission`, route-level role constraints).  
Target: Laravel middleware + schema + observed behavior.

## Role/permission parity matrix

| Item | Flask canonical | Laravel observed | Class | Evidence |
|---|---|---|---|---|
| Role-gated admin/member area isolation | Required | Implemented | MATCHED | Reverification: Admin `/admin/dashboard`=200, `/member/dashboard`=403; Member inverse 200/403. |
| Role middleware enforcement | Required | Implemented | MATCHED | `app/Http/Middleware/RoleMiddleware.php` enforces allowed role list and aborts 403. |
| Permission table parity | Required (`permission`) | Implemented (`permissions`) | MATCHED | Migration `2026_03_18_102600_create_permissions_and_audit_tables.php`. |
| Role-permission mapping parity | Required (`role_permission`) | Implemented (`role_permissions`) | MATCHED | Same migration; unique composite (`role_id`,`permission_id`). |
| Action-level permission code enforcement (`billing.generate`, `report.export`, etc.) | Canonical expects explicit permission semantics | Not fully demonstrated in route/controller checks (primarily role-based) | PARTIAL | Routes use `role:*` middleware; no audited evidence of per-permission gate checks in controllers/routes for each code. |
| Audit log visibility permission (`audit.view`) and UI route | Required for traceability | Data exists, UI contract route missing | PARTIAL | `audit_logs` table exists and writes verified; `/admin/audit-log` route not wired in `routes/web.php`. |
| Password reset token governance | Required | Implemented schema + service usage | MATCHED | `password_reset_tokens` migration + AuthController methods invoking PasswordResetService. |
| Month governance permission boundary | Required super-admin guardrails | Implemented by route middleware role lists | PARTIAL | Endpoints exist and execute; parity route contract differs from Flask; permission granularity by code not fully evidenced. |

## Launch-critical permission verdict
- Core role boundary enforcement: **PASS**
- Permission schema presence: **PASS**
- Fine-grained permission-code enforcement parity: **PARTIAL**
- Audit UI accessibility for auditors/admins: **PARTIAL**

## Quantified classification counts
- MATCHED: 5
- PARTIAL: 3
- MISSING: 0
- BEHAVIOR_MISMATCH: 0
- NOT_IN_SCOPE: 0
