# 80 MONTHLY DEPT JOURNAL PARITY AUDIT

## Scope
This audit is limited to one blocker only:
- monthly billing department ledger parity
- monthly billing journal parity
- related correction/reset behavior for the same accounting chain

## Flask truth

### Normal monthly billing generation
Source audited: `mess_billing_mvp_phase6_ui_workflow/app.py`

Verified behavior from Flask source:
- member-ledger `BILL` row is posted per bill
- journal voucher is posted per bill
- aggregated department-ledger posting is created per department per month

Observed source-level truth:
- billing generation creates billing rows first
- then posts member ledger per bill
- then posts one journal voucher per bill
- then aggregates department totals and posts department-ledger debit rows by department for the month

Reference shape found in Flask:
- member ledger:
  - `ref_type = "BILL"`
  - `ref_id = bill.id`
- journal voucher:
  - `voucher_type = "BILLING"`
  - `reference_type = "BILL"`
  - `reference_id = bill.id`
  - lines found in source:
    - DR `1100` (AR)
    - CR `4100` (Mess Revenue)
- department ledger:
  - one aggregated posting per department per month
  - `ref_type = "BILL"`
  - `ref_id = int(month_cycle.replace('-', ''))`
  - debit amount = sum of monthly net payable for that department

### Billing correction truth found in Flask
Inspected Flask correction path shows:
- member reversal posted through ledger adjustment/reversal flow
- department reversal/repost behavior exists in correction path
- full correction journal parity was **not** conclusively proven from the inspected correction snippets alone

### Hard reset truth found in Flask
Inspected evidence for this pass does **not** prove a full monthly billing hard-reset cleanup contract for department-ledger + journal artifacts.
Therefore reset parity cannot be claimed from source evidence inspected in this pass.

## Laravel current state

### Billing generation current branch state
Source audited: `app/Services/Billing/BillingGenerationService.php`

Current Laravel branch does:
- create billing rows
- create member-ledger `BILL` rows

Current Laravel branch does **not** do:
- monthly billing department-ledger posting parity matching Flask
- monthly billing journal voucher posting parity matching Flask

### Department ledger structure in current Laravel branch
Current Laravel branch `department_ledgers` shape is materially shallower than Flask truth.
Observed columns are limited to:
- `department_id`
- `mess_id`
- `entry_date`
- `entry_type`
- `amount`
- `reference_type`
- `reference_id`
- `remarks`

It does not currently expose Flask-style running-balance / debit-credit ledger semantics for this monthly billing parity target.

### Journal/accounting substrate in current Laravel branch
For this blocker, the critical finding is:
- current Laravel branch does **not** yet have an honest journal substrate for monthly billing parity
- no proven ready-to-use Laravel journal voucher / journal lines accounting implementation was available in current branch scope for this blocker pass

## Exact parity gap
Flask proves monthly billing generation downstream accounting behavior that Laravel still lacks:
1. journal voucher per bill
2. aggregated department-ledger posting per department per month

Because the current Laravel branch lacks the honest journal substrate for that parity, exact truthful implementation cannot be claimed.

## Implementation decision
- **Decision:** `BLOCKED`
- **Reason:** Laravel lacks honest journal substrate for monthly billing parity, and current `department_ledgers` shape is materially shallower than Flask truth.
- **What was not done:** no fake implementation, no placeholder journal behavior, no broad accounting invention.

## Why this decision is justified
The prompt for this pass explicitly forbids fake parity and placeholder accounting behavior.
Given the current Laravel accounting substrate, an honest exact parity implementation is not available inside this scoped docs-only blocker pass.

## Blocker result
- **Blocker status:** `BLOCKED`
- **Branch consequence:** remains **NO-GO for cPanel push**
