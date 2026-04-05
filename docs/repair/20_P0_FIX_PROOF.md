# 20 P0 FIX PROOF

## P0-1 Billing generation parity

### What was broken
Baseline `app/Services/Billing/BillingGenerationService.php`:
- ignored closed month state
- always used daily attendance counts
- did not use approved locked monthly attendance as primary truth
- did not clamp charged days to employment window after member inclusion
- scope/config hash did not include monthly attendance snapshot, so rerun semantics were blind to approved monthly attendance changes

### Exact files changed
- `app/Services/Billing/BillingGenerationService.php`
- `tests/Feature/RepairFinancialFlowsTest.php`

### Before vs after behavior
**Before**
- generation could run even if month closure said closed
- approved monthly attendance was ignored
- rerun idempotency was based on rate config only

**After**
- generation checks `MonthClosure` + `BillingCycle.is_closed`
- approved locked `MonthlyAttendance` is primary input; daily attendance is fallback only
- present days are clamped to join/leave window
- config hash now includes monthly attendance snapshot
- ledger posting remains one bill debit per generated bill

### Command / test proof
```powershell
php artisan test --filter=RepairFinancialFlowsTest
```
Latest result stored in `storage/logs/repair_financial_test_output.txt`:
- `Tests: 10 warnings (32 assertions)`
- Focused suite completed without failing assertions; warnings come from existing `file_get_contents(...\.env)` calls in pre-existing tests.

### Fix state
- **Partially fixed**
- Proven repaired: month closure enforcement, monthly attendance primacy, fallback semantics, employment window clamp, idempotency hash improvement, member ledger post
- Not proven repaired: department ledger / journal posting for normal monthly billing because Laravel codebase/truth pack did not provide enough executable target behavior in current repo

---

## P0-2 Billing correction parity

### What was broken
Baseline `app/Services/Billing/BillingCorrectionService.php` only overwrote billing row fields and audit-logged the change.

### Exact files changed
- `app/Services/Billing/BillingCorrectionService.php`
- `app/Services/LedgerToolchainService.php` (verified reuse, no code change in this pass)
- `tests/Feature/RepairFinancialFlowsTest.php`

### Before vs after behavior
**Before**
- `billings.net_payable` changed
- no downstream ledger recompute guaranteed after correction insert/replace

**After**
- correction computes delta between old and new payable
- writes `BILL_CORRECTION` member-ledger row (debit for increase, credit for decrease)
- immediately recomputes member ledger balances so later rows stay internally truthful
- remains audit logged
- duplicate correction ledger for same bill is replaced before repost

### Command / test proof
```powershell
php artisan test --filter=RepairFinancialFlowsTest
```
Focused regression now proves:
- `test_billing_correction_posts_delta_to_member_ledger`
- `test_billing_correction_recomputes_downstream_ledger_balances`

Observed recompute truth in test:
- correction row settles at `650.00`
- downstream payment row settles at `800.00`

### Fix state
- **Materially fixed for member-ledger truth**
- Proven repaired: correction delta posting + downstream `balance_after` recompute
- Not proven repaired: department/journal side effects because no executable department-billing correction path existed in current Laravel repo

---

## P0-3 Month hard reset parity

### What was broken
Baseline `app/Services/MonthClosureService.php` deleted only `billings` for the month.
It left:
- stale `member_ledgers` for monthly bills/corrections
- stale `billing_runs`
- potentially stale downstream `balance_after` values on remaining ledger rows
- unsynced `billing_cycles`

### Exact files changed
- `app/Services/MonthClosureService.php`
- `tests/Feature/RepairFinancialFlowsTest.php`

### Before vs after behavior
**Before**
- hard reset was destructive only to bill rows
- financial truth remained orphaned or stale

**After**
- month-scoped `BILL` and `BILL_CORRECTION` member-ledger rows for affected billing ids are removed
- `billing_runs` for the month are removed
- `billing_cycles` is synced back to open state
- affected members are recomputed so surviving ledger rows have truthful `balance_after`
- closure event remains audit logged

### Command / test proof
```powershell
php artisan test --filter=RepairFinancialFlowsTest
```
Focused regression now proves:
- `test_hard_reset_removes_billing_ledgers_and_runs`
- `test_hard_reset_recomputes_remaining_member_ledgers`

Observed recompute truth in test:
- surviving payment ledger settles at `-300.00` after hard reset removes the month bill

### Fix state
- **Materially fixed**
- Proven repaired: billing rows, billing-run metadata, member-ledger cleanup, downstream recompute
- No separate journal artifacts existed in current schema to repair

---

## P0-4 Payment / ledger truth

### What was broken
Baseline payment approval flow in `app/Http/Controllers/Admin/PaymentController.php` posted ledger in controller after manual verify, with insufficient exact-once guard relative to reconciliation lifecycle.

### Exact files changed
- `app/Http/Controllers/Admin/PaymentController.php`
- `app/Services/Payments/PaymentTransactionService.php`
- `tests/Feature/RepairFinancialFlowsTest.php`

### Before vs after behavior
**Before**
- approve path could attempt invalid lifecycle transitions on repeated calls
- ledger post was not formally guarded against duplicate route execution by ledger lookup + expanded status short-circuit

**After**
- approve exits early for `SUCCESS`, `RECONCILIATION_PENDING`, and `RECONCILED`
- manual verify does not re-transition already successful/reconciliation-pending payments
- ledger row is written only if no `PAYMENT/ref_id=payment.id` ledger row exists
- reconciliation row still gets created once

### Command / test proof
```powershell
php artisan test --filter=RepairFinancialFlowsTest
```
Focused regression continues to prove duplicate approve calls do not create duplicate payment ledger rows.

### Fix state
- **Partially fixed**
- Proven repaired: exact-once member-ledger posting and coherent approve behavior
- Remaining limitation: reports index had one legacy paid-status usage and is repaired in this pass separately below

---

## P0-5 Guest / department financial posting

### What was broken
Baseline `app/Http/Controllers/Admin/GuestController.php::approveMeal()` only called `$meal->touch()`.
No chargeback or department-ledger truth existed.

### Exact files changed
- `app/Http/Controllers/Admin/GuestController.php`
- `tests/Feature/RepairFinancialFlowsTest.php`

### Before vs after behavior
**Before**
- workflow looked approved without any financial effect

**After**
- guest meal approval resolves department by guest department name
- resolves first matching mess for that department
- writes/updates `department_ledgers` row with `DEBIT` chargeback keyed by `GuestMeal::class + meal id`
- delete path now cleans linked department-ledger row

### Command / test proof
```powershell
php artisan test --filter=RepairFinancialFlowsTest
```
Focused regression continues to prove real financial side effect.

### Fix state
- **Fixed within available schema**
- Limitation: guest model stores department as plain text, so chargeback depends on department-name matching rather than FK truth
