# 82 MONTHLY ACCOUNTING SCHEMA PROPOSAL

## Goal
Define the minimum honest schema needed to implement Flask-aligned monthly billing department/journal accounting parity.

## Proposed journal tables

### `journal_vouchers`
Required fields:
- `id` bigint PK
- `voucher_no` string unique
- `voucher_type` string indexed
- `reference_type` string indexed
- `reference_id` bigint indexed
- `month_cycle` string(7) indexed
- `voucher_date` date indexed
- `status` string default `POSTED`
- `posted_by_user_id` foreign key -> `users.id`
- `reversal_of_voucher_id` nullable FK self-reference
- `origin_reference_type` nullable string
- `origin_reference_id` nullable bigint
- `created_at`
- `updated_at`

Constraints:
- unique index on `voucher_type + reference_type + reference_id + reversal_of_voucher_id(null-safe strategy as implementation detail)` to block duplicate initial postings
- index on `month_cycle`

### `journal_lines`
Required fields:
- `id` bigint PK
- `journal_voucher_id` FK -> `journal_vouchers.id` cascade delete
- `account_code` string indexed
- `debit` decimal(14,2) default 0
- `credit` decimal(14,2) default 0
- `description` nullable string
- `source_type` nullable string
- `source_id` nullable bigint
- `created_at`
- `updated_at`

Constraints:
- check: not both zero
- check: debit/credit non-negative
- index on `source_type + source_id`

## Proposed account master
If no honest COA substrate exists in branch, add:

### `coa_accounts`
Fields:
- `id` bigint PK
- `account_code` string unique
- `account_name` string
- `account_type` string
- `is_active` bool
- `created_at`
- `updated_at`

Minimum billing-required default accounts:
- `1100` Accounts Receivable
- `4100` Mess Revenue

## Proposed department ledger redesign
Current table is too shallow. Proposed target is either migration-in-place or replace-with-new-ledger-shape.
Preferred path: migrate existing `department_ledgers` in place if safe.

### Target `department_ledgers` shape
Required fields:
- `id` bigint PK
- `department_id` FK -> `departments.id`
- `mess_id` nullable FK -> `messes.id`
- `month_cycle` nullable string(7) indexed
- `entry_date` date indexed
- `debit` decimal(14,2) default 0
- `credit` decimal(14,2) default 0
- `balance_after` decimal(14,2) default 0
- `reference_type` string indexed
- `reference_id` bigint indexed
- `reason_code` nullable string indexed
- `origin_reference_type` nullable string indexed
- `origin_reference_id` nullable bigint indexed
- `aggregation_key` nullable string unique-for-initial-monthly-bill-posting intent
- `posted_by_user_id` nullable FK -> `users.id`
- `remarks` nullable text
- `created_at`
- `updated_at`

Why needed:
- debit/credit polarity for reversal semantics
- running balance for ledger truth
- month aggregate traceability
- no-duplicate aggregate posting control
- correction/reset origin tracing

## Proposed example rows

### Example journal voucher for one bill
`journal_vouchers`
- `voucher_type = BILLING`
- `reference_type = BILL`
- `reference_id = 145`
- `month_cycle = 2026-03`
- `voucher_date = 2026-03-31`

`journal_lines`
- line 1: `account_code = 1100`, `debit = 6797.04`, `credit = 0`
- line 2: `account_code = 4100`, `debit = 0`, `credit = 6797.04`

### Example department ledger monthly aggregate row
- `department_id = 4`
- `month_cycle = 2026-03`
- `entry_date = 2026-03-31`
- `debit = 152340.00`
- `credit = 0`
- `reference_type = BILL_MONTH_AGGREGATE`
- `reference_id = 202603`
- `aggregation_key = dept-bill-2026-03-4`

## Migration plan (design only)
1. add accounting master + voucher substrate
2. upgrade `department_ledgers` to real ledger shape
3. backfill nullable-safe defaults for existing guest/department rows if needed
4. add indexes/uniques after data backfill safety checks

## Delete / reversal behavior
- never rely on silent cascading delete for accounting truth
- hard reset should explicitly target monthly billing-created vouchers and department-ledger aggregates by month/reference markers
- reversal rows preferred over destructive delete if live accounting/audit policy requires retention
- if reset semantics in current product require delete, cleanup must still be explicit and reference-targeted

## Schema result
- **Status:** DESIGN READY
