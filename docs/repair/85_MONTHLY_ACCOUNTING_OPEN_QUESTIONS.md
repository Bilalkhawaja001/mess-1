# 85 MONTHLY ACCOUNTING OPEN QUESTIONS

## 1. Correction journal exact parity in Flask
- **Classification:** `MUST_RESOLVE_BEFORE_CODING`
- Why: inspected Flask snippets proved correction department reversal/repost behavior, but did not conclusively prove exact journal correction behavior.
- Safe next action: inspect broader Flask correction/journal code path before coding the Laravel correction accounting layer.

## 2. Hard reset accounting policy: explicit delete vs reversal vouchers
- **Classification:** `MUST_RESOLVE_BEFORE_CODING`
- Why: inspected Flask evidence for monthly billing hard-reset cleanup of journal/department artifacts was incomplete.
- Need decision before coding: whether reset should delete generated accounting artifacts or create reversal entries.

## 3. Historical department mapping source for billing month
- **Classification:** `SAFE_DEFAULT_AVAILABLE`
- Safe default: use billing-time member-org/department mapping captured at generation time, and use that same department identity for downstream accounting traceability.

## 4. Whether to extend current `department_ledgers` or stage a parallel new table then migrate consumers
- **Classification:** `SAFE_DEFAULT_AVAILABLE`
- Safe default: extend current table in place if migration/backfill risk is manageable; otherwise stage migration only if schema conversion proves unsafe.

## 5. Whether current repo already contains hidden accounting substrate outside inspected scope
- **Classification:** `NON_BLOCKING`
- Reason: current design can still be frozen; implementation pass should just recheck before writing migrations.

## 6. Voucher number generation format
- **Classification:** `NON_BLOCKING`
- Reason: any deterministic unique voucher-number scheme is acceptable as long as business uniqueness and traceability hold.

## Open-questions result
- **Status:** DESIGN READY
