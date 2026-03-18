# Flask → Laravel Parity Audit (Phase 1 Discovery)

Date: 2026-03-18  
Canonical baseline: `mess_billing_mvp_phase6_ui_workflow` (Flask)  
Target assessed: `mess_billing_laravel_app` (Laravel)

## Scope
This audit covers discovery/inventory only (no implementation):
- routes
- pages/templates
- modules
- forms/input surfaces
- models
- DB tables
- workflows
- reports/exports
- roles/permissions
- settings
- uploads/utilities/billing logic

## Snapshot Summary
- Flask routes: **126** (method-expanded ~**155**)
- Laravel routes: **37**
- Flask templates: **71**
- Laravel views: **20**
- Flask models: **48**
- Laravel models: **13**
- Flask table footprint (from model tablenames): **48 tables**
- Laravel migration tables: **13 tables**

## High-Level Finding
Laravel currently implements a **core subset** (auth + billing fundamentals), but Flask includes a much larger ERP-like scope: inventory, procurement, meals, guests, department/mess accounting, export/download center, ops hubs, and lifecycle controls (month close/reopen/reset). Parity gap is substantial and structural.

## Gap Matrix (Domain-Level)

| Domain | Flask | Laravel | Status |
|---|---|---|---|
| Auth login/logout | Yes | Yes | Partial parity |
| Password reset/change password | Yes | No | Missing |
| User/member management | Yes | Yes | Partial |
| Attendance (daily/monthly) | Yes | Yes | Partial |
| Extras/rates/billing/payments/ledger | Yes | Yes | Partial |
| Month close/reopen/hard reset | Yes | No | Missing |
| Inventory (items/stock/uom/txns/count) | Yes | No | Missing |
| Procurement (vendors/PO/GRN) | Yes | No | Missing |
| Meal planning/recipes/kitchen issue | Yes | No | Missing |
| Guest management + guest meals | Yes | No | Missing |
| Reports/download center/export endpoints | Yes | Limited pages | Missing/Partial |
| Department/mess accounting | Yes | No | Missing |
| Member self-service full pages | Yes | Dashboard only | Partial |
| Role-permission + audit log | Yes | Basic roles only | Partial |
| Ops/admin hubs | Yes | No | Missing |

## Routes Assessment
- Exact/near-exact parity exists for some core endpoints (`/members`, `/attendance`, `/extras`, `/rates`, `/billing`, `/payments`, `/ledger`, `/summary`, `/reports`, `/statement`, `/settings`, `/users`).
- Large unmatched surface remains (**130 method-endpoints unmatched** in direct route comparison).

## Pages/UI Assessment
- Laravel has admin list/index pages for core modules but lacks dedicated pages for large Flask feature sets:
  - inventory/procurement
  - meals/kitchen
  - guests/guest meals
  - downloads/report hubs
  - recovery + finance report pages
  - admin ops hubs
  - member portal pages beyond dashboard

## Data Model & Schema Assessment
- Flask uses significantly broader schema (48 model tables in `app.py`) including procurement, stock, meals, guest billing, department ledger, accounting journal, permissions, and audit features.
- Laravel migrations currently cover core billing schema only (13 tables).
- Key missing table families in Laravel:
  - `permission`, `role_permission`, `audit_log`
  - inventory/stock tables
  - procurement tables
  - meals/kitchen tables
  - guest + department/mess accounting tables
  - accounting journal/COA tables

## Workflows Not Yet Ported
1. Month-end governance (close/reopen/hard reset)
2. Ledger imports/opening balances/recompute tooling
3. Inventory issue-to-consumption chain
4. Procurement approval chain (PO/GRN)
5. Meal planning and kitchen issue approvals
6. Guest meal approval/export flow
7. Download center & bulk exports

## Reports / Export Parity Gaps
Flask exposes multiple file-export endpoints (CSV/XLSX/payroll/ledger/statement/attendance/guest meals). Laravel currently has report pages but not equivalent export surface.

## Roles / Permissions / Settings Gaps
- Flask has explicit permission mapping tables and audit log route.
- Laravel has role model but no discovered permission mapping equivalent and no audit log route page.
- Settings in Laravel exist but are less granular than Flask admin settings tabs and app-level operations.

## Upload / Utility Gaps
Missing parity for:
- member bulk upload/sample
- items CSV import/sample
- ledger/opening balance imports
- debug/ops utility surfaces

## Blockers for Phase 2 Mapping
1. **Monolith-to-modular translation challenge:** Flask business logic is concentrated in `app.py`; Laravel is controller/request/service segmented. Need a function-by-function extraction map first.
2. **Schema divergence:** table names and granularity differ; migration strategy required (new migrations + data conversion mapping).
3. **Route semantics mismatch:** same business actions exposed with different route shapes/methods (exact mapping must be canonicalized before implementation).
4. **Authorization model gap:** permission/audit behavior from Flask not yet represented in Laravel.
5. **Export/report contracts undefined:** output formats and filters need explicit contract docs before coding.
6. **Member portal split unresolved:** current Laravel route collisions around dashboard indicate role-based route namespace cleanup required before parity work.

## Artifacts Generated
- `FLASK_FEATURE_INVENTORY.md`
- `LARAVEL_FEATURE_INVENTORY.md`
- `MISSING_MODULES.json`
- `MISSING_PAGES.json`
- `MISSING_FUNCTIONS.json`

Phase 1 discovery complete.
