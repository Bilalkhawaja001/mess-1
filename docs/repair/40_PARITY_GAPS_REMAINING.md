# 40 PARITY GAPS REMAINING

This file lists only real remaining gaps after the latest blocker-formalization pass.

## 1. Monthly billing department/journal parity
- **Classification:** `BLOCKING`
- **Status:** `BLOCKED`
- **Flask truth confirmed:**
  - member-ledger `BILL` rows per bill
  - journal voucher per bill
  - aggregated department-ledger posting per department per month
- **Laravel gap:** Laravel lacks honest journal substrate for monthly billing parity.
- **Additional schema gap:** current `department_ledgers` shape is materially shallower than Flask truth.
- **What was done in this pass:** blocker documented truthfully only.
- **What was not done:** no fake implementation was added.
- **Evidence:** `docs/repair/80_MONTHLY_DEPT_JOURNAL_PARITY_AUDIT.md`

## 2. Kitchen approval semantics
- **Classification:** `BLOCKING`
- **Current state:** Approval endpoints are explicit-safe and no longer misleading, but they are still not full-parity proven workflows.

## 3. Procurement approval semantics
- **Classification:** `BLOCKING`
- **Current state:** PO approval is mainly a status transition; GRN approval acknowledges stock was already posted at GRN creation.

## 4. Guest department linkage by free-text department name
- **Classification:** `BLOCKING`
- **Current state:** Guest meal chargeback still depends on matching `guests.department` text to a real department name.

## 5. Special costing subsystem parity
- **Classification:** `DEFERRED_BY_SCOPE`
- **Current state:** Not part of this blocker-formalization pass.

## 6. Full test baseline cleanliness
- **Classification:** `BLOCKING`
- **Current state:** full `php artisan test` still returns warning-heavy output instead of a clean baseline.
