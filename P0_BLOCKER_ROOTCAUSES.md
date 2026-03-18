# P0_BLOCKER_ROOTCAUSES

Date: 2026-03-18 (Asia/Karachi)
Repo: `mess_billing_laravel_app`

## Pre-fix failing steps (captured before patch)

Request/response evidence:

- `GET /admin/dashboard` (unauth) -> **500**
  - Root cause: auth middleware redirect expected route name `login`, but app only defined `login.form`.
- `GET /admin/month-governance` -> **404**
  - Root cause: Month governance controller existed but routes were missing.
- `GET /admin/billing/1/correct` -> **404**
  - Root cause: billing correction route/action not wired.
- `GET /admin/payments/1/edit` -> **404**
  - Root cause: payment edit route/action not wired.
- `GET /admin/ledger/import` -> **404**
  - Root cause: ledger toolchain routes (import/recompute) not wired.
- `GET /admin/summary?month_cycle=2026-03&export=xlsx` -> failed (pre-fix no usable xlsx flow)
  - Root causes:
    1) `SummaryFilterRequest` only allowed `export=csv`.
    2) Summary controller had CSV only (no XLSX path).

## Additional root causes found during fix pass

- Billing correction 500 during first patched run:
  - SQL unique constraint failure on `billings(month_cycle, member_id)`.
  - Previous correction strategy attempted to create a replacement billing row with same `(month_cycle, member_id)`.
- Audit log gaps:
  - Critical P0 actions were not all reachable through routes; therefore expected audit rows were never generated.
  - Export actions had no audit logging.
