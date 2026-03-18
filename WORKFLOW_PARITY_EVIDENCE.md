# WORKFLOW_PARITY_EVIDENCE

Date: 2026-03-18

Canonical workflow source: Flask routes/behavior in `route_inventory.csv` and `app.py`  
Execution evidence source (Laravel): `P0_FINAL_REVERIFICATION.md`, `FINAL_P0_UAT_REPORT.md`, `routes/web.php`, controllers/services.

## Launch-critical workflow evidence

### 1) Auth login/logout/password workflows
- **Classification:** BEHAVIOR_MISMATCH (overall auth contract)
- **Route evidence:**
  - Laravel has `/login` GET/POST and `/logout` POST (`routes/web.php:23-26`).
  - Password routes are implemented as admin POST endpoints: `/admin/auth/password-reset/request`, `/admin/auth/password-reset/consume`, `/admin/auth/password-change` (`routes/web.php:37-39`), while canonical Flask contracts are `/password-reset/request` GET/POST, `/password-reset` GET/POST, `/change-password` GET/POST.
- **Execution evidence:**
  - `POST /login -> HTTP/1.1 302 Found` (reverification).
  - `POST /admin/auth/password-reset/request -> 302`; `POST /admin/auth/password-change -> 302` (reverification).
- **Output/behavior comparison:** feature exists but URI + page-level flow differs from Flask.

### 2) Member lifecycle (create/update/activate/deactivate)
- **Classification:** PARTIAL
- **Route evidence:** `/admin/members` GET/POST, `/admin/members/{member}` PUT, `/admin/members/{member}/toggle-active` POST (`routes/web.php:46-49`).
- **Gap evidence:** Flask includes explicit deactivate/reactivate/remove/bulk routes (`route_inventory.csv`), bulk/sample contracts are absent in Laravel.
- **Behavior note:** Base lifecycle works, but canonical management surface is reduced.

### 3) Attendance (daily + monthly controls)
- **Classification:** PARTIAL
- **Route evidence:** daily and monthly capture exist (`routes/web.php:51-54`).
- **Gap evidence:** canonical monthly governance endpoints `/attendance-monthly/approve` and `/attendance-monthly/unlock` are not present in Laravel routes.

### 4) Rate policy -> billing run
- **Classification:** PARTIAL
- **Route evidence:** `/admin/rates` + `toggle-approve` and `/admin/billing/generate` exist (`routes/web.php:59-65`).
- **Gap evidence:** canonical includes rate lock/update/delete controls; these route contracts are absent.

### 5) Billing correction with audit trail
- **Classification:** MATCHED
- **Route evidence:** `/admin/billing/{billing}/correct` exists (`routes/web.php:66`).
- **Execution evidence:** `POST /admin/billing/{id}/correct -> 302 Found` (reverification).
- **Output/behavior evidence:** audit trail contains `billing.corrected`; schema has correction/reversal support (`2026_03_18_102700_add_p0_workflow_columns.php`).

### 6) Payment post -> edit -> approve
- **Classification:** MATCHED
- **Route evidence:** `/admin/payments` POST, `/admin/payments/{payment}/edit`, `/admin/payments/{payment}/approve` (`routes/web.php:68-71`).
- **Execution evidence:**
  - `POST /admin/payments/{id}/edit -> 302 Found`
  - `POST /admin/payments/{id}/approve -> verified in prior UAT set`
- **Output/behavior evidence:** audit action `payment.edited` observed in reverification log set.

### 7) Ledger import + recompute
- **Classification:** BEHAVIOR_MISMATCH
- **Route evidence:** Laravel uses `/admin/ledger/import` and `/admin/ledger/recompute` (`routes/web.php:80-81`), canonical uses `/admin/import-ledger`, `/admin/import-opening-balances`, `/admin/recompute-ledger`.
- **Execution evidence:** both Laravel endpoints return `302 Found` in reverification and produce audit actions `ledger.opening_imported`, `ledger.recomputed`.
- **Behavior comparison:** workflow works but canonical route contract differs and opening-balance flow is collapsed into ledger import endpoint.

### 8) Month governance (close/reopen/hard reset)
- **Classification:** BEHAVIOR_MISMATCH
- **Route evidence:** Laravel exposes `/admin/month-governance/close|reopen|hard-reset` (`routes/web.php:74-76`) vs canonical `/month-close|/month-reopen|/month-reset-hard`.
- **Execution evidence:** all three actions executed with `302 Found`; audit actions include `month.closed`, `month.reopened`, `month.hard_reset`.
- **Behavior comparison:** control works; endpoint contract is not Flask-equivalent.

### 9) Reports/exports (summary/statement/reports)
- **Classification:** PARTIAL
- **Route evidence:** `/admin/summary`, `/admin/reports`, `/admin/statement` exist (`routes/web.php:83-85`).
- **Execution evidence:**
  - `GET /admin/summary?month_cycle=2026-03&export=csv -> 200 OK`
  - `GET /admin/summary?month_cycle=2026-03&export=xlsx -> 200 OK`
  - CSV header evidence: `Member Code,Member Name,Net Payable`
  - XLSX cell evidence: `A1=Member Code`, `C2=1999`
- **Gap evidence:** canonical bills-download endpoint family (`/reports/bills-download/...`) not present.

### 10) Audit log UI accessibility
- **Classification:** MISSING
- **Evidence:** `Admin\AuditLogController` + `admin/audit/index.blade.php` exist, but no `/admin/audit-log` route wiring in `routes/web.php`.

## Full-parity remaining workflows (non-launch)
- Inventory/procurement chain: **MISSING**
- Meal planning/kitchen issue chain: **MISSING**
- Guest billing and guest exports: **MISSING**
- Department/mess accounting workflows: **MISSING**

## Quantified workflow classification counts
- MATCHED: 2
- PARTIAL: 4
- MISSING: 1
- BEHAVIOR_MISMATCH: 3
- NOT_IN_SCOPE: 0
