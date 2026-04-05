# 84 MONTHLY ACCOUNTING IMPLEMENTATION PLAN

## Scope
File-by-file implementation plan for the next coding pass only.
No implementation is done in this pass.

## Proposed implementation order

### Phase A — schema substrate
1. add COA/account master if absent
2. add `journal_vouchers`
3. add `journal_lines`
4. upgrade `department_ledgers` into real ledger shape
5. add indexes / unique constraints / origin fields / aggregation key

### Phase B — models
Create/upgrade models:
- `App\Models\CoaAccount`
- `App\Models\JournalVoucher`
- `App\Models\JournalLine`
- extend `App\Models\DepartmentLedger`

### Phase C — service layer
Add dedicated services:
- `App\Services\Accounting\JournalPostingService`
- `App\Services\Accounting\DepartmentAggregatePostingService`
- `App\Services\Billing\MonthlyBillingAccountingService`

Responsibilities:
- `JournalPostingService`: create voucher + lines idempotently
- `DepartmentAggregatePostingService`: create/update monthly department aggregate postings idempotently
- `MonthlyBillingAccountingService`: orchestrate billing-linked accounting generation/correction/reset behavior

### Phase D — billing integration points
Update only these existing services in the coding pass:
- `BillingGenerationService`
- `BillingCorrectionService`
- `MonthClosureService`

Call flow target:
- generation -> create bill -> member ledger -> journal voucher -> department aggregate -> billing run finalize
- correction -> adjust bill truth -> accounting reverse/repost / aggregate recompute
- hard reset -> remove/reverse month-linked accounting artifacts -> recompute balances

## File-by-file future plan

### `app/Services/Billing/BillingGenerationService.php`
Add downstream call to `MonthlyBillingAccountingService::postGenerationArtifacts(...)` after each billing row and then finalize department aggregates once month generation loop completes.

### `app/Services/Billing/BillingCorrectionService.php`
Add downstream call to `MonthlyBillingAccountingService::postCorrectionArtifacts(...)` or equivalent reverse/repost orchestration.

### `app/Services/MonthClosureService.php`
Add downstream call to `MonthlyBillingAccountingService::cleanupMonthArtifacts(...)` for hard reset.

### `tests/Feature/RepairFinancialFlowsTest.php`
Add focused tests for:
- generation journal creation
- generation department aggregate creation
- rerun no-duplicate behavior
- correction reverse/repost
- hard reset cleanup

### new tests file if needed
Possible dedicated file:
- `tests/Feature/MonthlyBillingAccountingParityTest.php`

## Risk points
1. correction semantics at member-ledger layer currently use delta-row approach; must avoid contradiction with reversal/repost journal design
2. department mapping source must be deterministic for billing month
3. current shallow department ledger rows may need backfill/default handling
4. hard reset must not leave orphan journal lines or duplicate regeneration artifacts

## Rollback concerns
- schema changes must be backward-safe for existing department ledger consumers
- if migration-in-place for `department_ledgers` is risky, staged migration + backfill may be required
- accounting event deletion vs reversal policy must be decided before coding

## Expected tests in coding pass
- `test_billing_generation_posts_journal_voucher_per_bill`
- `test_billing_generation_posts_department_aggregate_per_department_month`
- `test_billing_rerun_does_not_duplicate_journal_or_department_aggregate`
- `test_billing_correction_reverses_and_reposts_accounting_truth`
- `test_hard_reset_cleans_monthly_billing_accounting_artifacts`
- `test_department_aggregate_matches_sum_of_bills_for_department`

## Implementation plan result
- **Status:** DESIGN READY
