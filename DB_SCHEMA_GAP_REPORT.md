# DB_SCHEMA_GAP_REPORT.md

## Scope
Table-level diff between Flask canonical schema (48 tables from `app.py`) and Laravel current migrations (13 tables).

## Current Baseline
- **Flask tables:** 48
- **Laravel tables:** 13 (`users`, `roles`, `members`, `attendances`, `monthly_attendances`, `extras`, `rate_policies`, `billings`, `billing_runs`, `billing_cycles`, `payments`, `member_ledgers`, `app_settings`)

---

## A) Launch-Critical Gaps (P0/P1)

### 1) Access control & audit
| Flask table | Laravel equivalent | Gap | Required migration work | Priority |
|---|---|---|---|---|
| `permission` | none | missing table | create `permissions` table (`code`, `description`, timestamps) | P0 |
| `role_permission` | none | missing pivot | create `role_permissions` (FK roles, FK permissions, unique composite) | P0 |
| `audit_log` | none | missing audit trail | create `audit_logs` (`actor_user_id`, `action`, `entity_type`, `entity_id`, `details/json`, timestamp, indexes) | P0 |
| `password_reset_token` | none | missing reset token persistence | create `password_reset_tokens` (`user_id`, `token_hash`, `expires_at`, `consumed_at`) | P0 |
| `month_closure` | none | missing month governance persistence | create `month_closures` (`month_cycle`, `closed_by`, `closed_at`, `reopened_by`, `reopened_at`, `reason`) | P0 |

### 2) Billing integrity deltas in existing overlapping tables
| Overlapping domain | Flask columns not represented in Laravel (expected) | Why needed | Priority |
|---|---|---|---|
| `billing` vs `billings` | `locked`, `is_reversed`, `reversal_reason_code`, `reversed_by`, `reversed_at`, `origin_ref_type`, `origin_ref_id` | correction/reversal traceability + lock state | P0 |
| `payment` vs `payments` | robust status lifecycle (`DRAFT/APPROVED`), `approved_by`, `approved_at`, `reason_code`, `origin_ref_*` | approval governance + traceability | P0 |
| `member_ledger` vs `member_ledgers` | `reason_code`, `approved_by`, `origin_ref_*`, deterministic running balance controls | reconciliation + forensic tracing | P0 |
| `rate_policy` vs `rate_policies` | lock/approval semantics parity with Flask toggles | prevent unauthorized rate drift | P1 |
| `members` | Flask uses soft-delete + lifecycle dates (`leave_date`, deletion flags) and strict ID constraints | member lifecycle parity + historical reporting | P1 |

### 3) Missing indexes/FKs (critical)
- Composite uniqueness and idempotency keys (e.g., billing run uniqueness by month+scope hash) must remain enforced.
- Add FK constraints for `approved_by`, `reversed_by`, `posted_by`, and token ownership.
- Add indexes on report-critical filters: `month_cycle`, `member_id/member_pk`, `status`, `date`.
- Add unique constraints for permission codes and role-permission composite.

---

## B) Full-Parity Gaps (P2, non-launch)

### Inventory + Procurement tables missing
- `item`, `uom_conversion`
- `stock_txn`, `stock_txn_line`, `stock_balance`, `stock_count`, `stock_count_line`
- `vendor`, `po`, `po_line`, `grn`, `grn_line`

### Meals + Kitchen tables missing
- `menu`, `recipe_line`, `meal_plan`, `expected_consumption`, `meal_issue_link`
- `kitchen_issue`, `kitchen_issue_line`

### Guest + Department/Mess + Finance tables missing
- `guest`, `guest_meal`
- `department`, `mess`, `mess_monthly_records`, `mess_expense_lines`, `mess_attendance`, `mess_bill_print_admin`
- `member_org`, `department_ledger`
- `coa_accounts`, `journal_voucher`, `journal_lines`

---

## C) Data Migration Notes

1. **Naming normalization risk**: Flask singular names (`member`, `payment`) vs Laravel pluralized defaults (`members`, `payments`). Need deterministic mapping dictionary for ETL.
2. **PK/FK alignment**: Flask uses `member_pk` references; Laravel may use `member_id` relation helpers. Preserve source IDs or create mapping table.
3. **Status enum harmonization**: align approval/lock status values exactly before importing historic data.
4. **Month cycle formatting**: preserve `YYYY-MM` invariant and check constraints.
5. **Audit backfill**: import historical actor/action data where available; otherwise mark as migrated seed event.

---

## D) Recommended Migration Sequencing

### Sequence 0 (pre-check)
- Freeze schema contract doc (column-level mapping for overlapping tables).
- Define id-mapping strategy for users/members.

### Sequence 1 (P0 security/governance)
1. `permissions`
2. `role_permissions`
3. `audit_logs`
4. `password_reset_tokens`
5. `month_closures`

### Sequence 2 (P0 billing integrity upgrades on existing tables)
6. alter `billings` for reversal/lock/origin fields
7. alter `payments` for full approval lifecycle fields
8. alter `member_ledgers` for reason/approval/origin fields
9. tighten indexes/FKs across billing/payment/ledger tables

### Sequence 3 (P1 lifecycle enhancements)
10. member lifecycle parity columns and constraints
11. rate policy lock/update/delete governance fields
12. monthly attendance approval-lock structures

### Sequence 4 (P2 full parity modules)
13+. inventory/procurement tables
14+. meal/kitchen tables
15+. guest + department/mess + finance accounting tables

---

## E) Launch Readiness Schema Verdict
**Not launch-ready for full Flask-equivalent governance.** Laravel has core transactional tables but lacks mandatory security-governance tables and several billing-integrity columns needed for controlled production operation.
