# 83 MONTHLY ACCOUNTING POSTING RULES

## 1. Generation rules

### Member ledger
For every generated bill:
- create one member-ledger row
- `ref_type = BILL`
- `ref_id = billing.id`
- debit = `billing.net_payable`
- credit = `0`
- posting date = month end

### Journal
For every generated bill:
- create one journal voucher
- `voucher_type = BILLING`
- `reference_type = BILL`
- `reference_id = billing.id`
- voucher date = month end
- exactly two journal lines:
  - DR `1100` Accounts Receivable = net payable
  - CR `4100` Mess Revenue = net payable

### Department ledger
For each department in the generated month:
- aggregate all posted bill net payable totals for members mapped to that department
- create one department-ledger row for the month aggregate
- debit = total department monthly net payable
- credit = 0
- `reference_type = BILL_MONTH_AGGREGATE`
- `reference_id = int(month_cycle without dash)`
- `aggregation_key = dept-bill-{month_cycle}-{department_id}`

## 2. Rerun / idempotency rules

### Initial generation rerun
- existing `BillingRun(scope_hash)` remains first envelope guard
- no second bill row for same member/month
- no second journal voucher for same bill
- no second department aggregate row for same month/department aggregation key

### Regeneration after hard reset
- allowed only after prior month-created accounting artifacts are removed/reversed according to reset policy
- regenerated postings create fresh bill-linked vouchers and fresh department aggregate rows

## 3. Correction rules
Chosen correction strategy: **reverse old downstream accounting, then repost corrected downstream accounting**.

Why chosen:
- closest honest match to Flask correction pattern found in source
- cleaner audit trail than delta-only journal logic
- avoids ambiguity when rate, extras, and active-days all change together

### Member ledger correction rule
- retain current correction/member-ledger truth already present
- implementation pass may either keep explicit `BILL_CORRECTION` delta row or align to full reversal/repost pattern after impact review
- if mixed approach is kept, docs/tests must prove no contradiction with journal/department behavior

### Journal correction rule
For corrected bill:
1. post reversal voucher for old bill accounting
2. post fresh corrected bill voucher for current bill amount
3. both vouchers must reference original bill id through origin fields
4. duplicate correction rerun must not create repeated reversal/repost pairs

### Department ledger correction rule
For corrected bill’s department/month aggregate:
- either recompute the monthly aggregate row deterministically and replace its amount truthfully
- or post explicit reversal + repost rows

Chosen rule:
- use deterministic aggregate recompute for the month+department target row
- keep source traceability through reason/origin fields

Why chosen:
- Flask generation uses monthly aggregate, not per-bill department rows
- aggregate recompute is the cleanest parity-aligned way to keep department month truth consistent

## 4. Hard reset rules
For a hard reset of one `month_cycle`:
- delete/reset bill rows for the month as already done
- remove or reverse monthly billing-created member-ledger rows for that month
- remove or reverse journal vouchers whose references/origin markers tie them to billing rows in that month
- remove department aggregate rows whose `aggregation_key` / reference markers tie them to that month
- recompute downstream ledger balances after cleanup where ledger tables persist running balances

Chosen rule:
- member and department ledgers require recompute after targeted cleanup
- journal vouchers/lines do not require balance recompute because they are event rows, not running-balance rows

## 5. Traceability rules
Required trace chains:
- billing row -> member ledger by `ref_type/ref_id`
- billing row -> journal voucher by `reference_type/reference_id`
- journal voucher -> journal lines by FK
- month+department aggregate -> department ledger by `aggregation_key`
- correction/reversal rows -> original bill/voucher by `origin_reference_type/origin_reference_id`

## 6. No-duplicate rules
- unique billing row per member/month remains enforced at business logic level
- unique initial billing journal voucher per bill
- unique monthly department aggregate row per `aggregation_key`
- unique correction reversal/repost guard per original bill + reason code + month

## 7. Ambiguity note
Flask correction journal behavior was not fully proven from inspected snippets.
Chosen design therefore freezes the cleanest parity-aligned rule for the next implementation pass: reversal + repost at journal layer.

## Posting rules result
- **Status:** DESIGN READY
