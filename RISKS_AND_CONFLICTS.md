# RISKS AND CONFLICTS

## Confirmed risks from current code

### 1. Inline Blade `@php(...)` compile failures in procurement view
- Current view still contains inline `@php(...)` usage in at least one place (`@php($grnLine = $grn->lines->first())`).
- In live evidence, this compiled into invalid PHP and caused 500 errors.
- This is a real production risk and must be fixed before relying on page-level verification.

### 2. Complex `@json($collection->map(fn() => ...))` in Blade
- Current view still contains complex mapping logic inside `@json(...)` for GRN line payloads.
- This already caused a confirmed parse error in live evidence.
- Safe fix is to precompute plain arrays in `@php` blocks, then pass final arrays to `@json`.

### 3. GRN quantity math mixes PO-level and line-level received totals
- `storeGrn()` computes pending using:
  - selected line ordered qty
  - total received qty across **all** GRN lines on the PO
- This is only correct for single-line POs.
- For multi-line POs, one line’s receipts can incorrectly reduce another line’s pending qty.
- This conflicts with the current multi-line UI and must be corrected.

### 4. PO status recomputation uses only first line ordered qty
- In `storeGrn()`, PO status update compares:
  - first line ordered qty
  - total received qty across all GRN lines
- This is mathematically unsafe for multi-line POs.
- Existing status values can be preserved, but computation must be based on total ordered vs total received, or equivalent safe line-aware aggregation.

### 5. Unit price rule conflicts with requested business rule
- Current PO validation allows unit price zero.
- Requested rule requires unit price `> 0`.
- This is a safe tightening change, but may reject legacy flows that relied on zero/blank pricing.
- If tests/data depend on zero, those will need targeted updates.

### 6. Unit cost rule conflicts with current create behavior
- Current GRN create stores `unit_cost` from request and defaults missing values to `0`.
- Requested rule requires unit cost `> 0`.
- This is a safe tightening change, but it changes current permissive behavior.

### 7. Repeat-submission / duplicate-post risk on GRN create
- `storeGrn()` writes GRN + GRN line + stock transaction in one transaction, which is good.
- But there is no explicit idempotency token, unique constraint, or duplicate-submit guard.
- Browser double-submit could still create two GRNs if both requests pass before business checks diverge.
- Current architecture supports only limited realistic mitigation without redesign:
  - stricter pending re-check inside transaction
  - disable submit button client-side
  - avoid false claims of perfect concurrency protection

### 8. No dedicated GRN status field
- Requested status clarity mentions created/acknowledged behavior if fields already exist.
- Current schema has no GRN status field.
- Safe compatible implementation is display-level clarity only, not schema-heavy redesign.

## Safe compatibility boundary
- Safe to implement now:
  - stronger PO validation
  - stronger GRN validation
  - line-correct pending/received computation
  - prevent over-receipt using selected line totals
  - keep PO statuses but compute them safely
  - preserve stock posting on GRN create only
  - ensure approval/acknowledgement do not double-post
  - improve UI error clarity and submit safety without redesign
- Unsafe without further design proof:
  - new deep approval workflow engine
  - new GRN status schema unless explicitly required
  - claims of full concurrency correctness beyond current DB/app architecture

## Required audit conclusion before coding
- Procurement hardening is safely compatible with current codebase **if** changes remain localized to:
  - `ProcurementController`
  - `resources/views/admin/procurement/index.blade.php`
  - focused procurement tests
- No migration is required unless explicitly choosing to add a DB-level idempotency/uniqueness mechanism, which is outside current minimal safe scope.
