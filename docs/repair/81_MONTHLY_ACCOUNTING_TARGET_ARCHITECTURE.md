# 81 MONTHLY ACCOUNTING TARGET ARCHITECTURE

## Scope
Architecture-only blueprint for monthly billing department/journal accounting foundation.
No implementation is performed in this pass.

## Flask accounting truth carried forward
From the blocker audit and Flask source review, the target must preserve these truths:
1. billing generation posts member-ledger `BILL` rows per bill
2. billing generation posts one journal voucher per bill
3. billing generation posts one aggregated department-ledger row per department/month
4. journal is bill-linked; department ledger is month+department aggregate linked
5. correction has reversal/repost semantics in member + department flows; journal correction truth is only partially visible in inspected Flask snippets
6. reset/hard-reset must not leave orphan downstream accounting artifacts

## Chosen target architecture

### 1. Journal foundation
Use a classic two-layer accounting structure:
- `journal_vouchers`
- `journal_lines`

Why chosen:
- Flask truth explicitly uses voucher header + line rows
- monthly billing needs traceable per-bill accounting events
- later payment/guest/procurement parity can reuse same substrate
- this is the narrowest honest parity-aligned design

### 2. Department ledger foundation
Keep `department_ledgers` as the departmental receivable/control ledger, but redesign/extend it into a real ledger shape rather than a single shallow amount row.

Chosen direction:
- extend existing concept into a proper debit/credit ledger with running balance and origin traceability
- do **not** replace it with journal-only reporting, because Flask keeps department ledger as a separate operational truth surface

### 3. Posting ownership model
Billing generation flow should own posting through a dedicated accounting-posting service, not inline scattered DB writes.

Chosen layering:
- `BillingGenerationService`
  - generates bill rows and member-ledger rows
  - delegates downstream accounting posting to a dedicated monthly accounting poster
- `BillingCorrectionService`
  - delegates correction reversal/repost accounting to same accounting foundation
- `MonthClosureService`
  - delegates hard-reset cleanup/reversal to same accounting foundation

### 4. Aggregation boundary
Journal stays **per bill**.
Department ledger stays **aggregated per department per month**.

Why this split is chosen:
- matches Flask generation truth found in source
- keeps bill-level traceability in accounting
- keeps department control view compact and month-oriented

## Dependency map
- `Billing` -> source transaction truth
- `MemberLedger` -> member receivable truth
- `JournalVoucher` + `JournalLine` -> accounting truth per bill
- `DepartmentLedger` -> department month aggregate truth
- `BillingRun` -> rerun/idempotency envelope
- `MonthClosure` / hard reset -> cleanup/reversal controller
- COA/account master -> required for journal line posting

## Rejected alternatives

### Rejected A: Department ledger only, no journal foundation
Rejected because Flask truth explicitly posts journal vouchers per bill. This would preserve only half the accounting chain and remain dishonest parity.

### Rejected B: Journal only, no department ledger
Rejected because Flask keeps department-ledger posting as a separate surfaced truth for departmental receivables/control.

### Rejected C: One aggregated journal voucher per department/month
Rejected because Flask source shows journal posting per bill, not grouped monthly journal posting.

### Rejected D: Reuse current shallow `department_ledgers` row shape as-is
Rejected because current structure cannot carry real ledger semantics, running balance, debit/credit polarity, or reversal traceability.

## Architecture conclusion
The next coding pass should implement:
- voucher/line accounting substrate
- upgraded department ledger substrate
- centralized posting/orchestration service
- idempotent bill-level journal posting + department-month aggregate posting

## Design result
- **Status:** DESIGN READY
- Reason: source truth is strong enough to freeze the target architecture even though implementation is still pending.
