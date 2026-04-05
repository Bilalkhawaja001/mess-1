# 40 PARITY GAPS REMAINING

This file lists only real remaining gaps after the latest repair-branch pass.

## 1. Billing department/journal parity for normal monthly billing
- **Blocker:** Current Laravel schema/services do not expose a proven department-ledger or journal posting contract for standard member billing generation/correction beyond member ledger.
- **Why not fixed:** Truth pack available in workspace did not provide an executable, unambiguous mapping for monthly billing department/journal posting into the current Laravel schema.
- **Risk:** **High** if Flask truth expects downstream departmental accounting from every monthly billing run.

## 2. Kitchen approval semantics remain explicit-safe but still not full parity
- **Current state:** `KitchenController::approvePlan()` and `approveIssue()` no longer pretend to complete deeper accounting/inventory work; they acknowledge approval without inventing a missing side-effect.
- **Why still remaining:** Flask truth-pack did not provide a stronger executable approval contract, and current schema does not expose dedicated approval-state/accounting columns to support one honestly.
- **Risk:** **Medium**. Workflow is now explicit and safer, but still not a proven full-parity approval pipeline.

## 3. Procurement approval semantics remain shallow
- **Current state:** PO approval is still primarily a status transition; GRN approval acknowledges that stock was already posted at GRN creation and moves PO to `RECEIVED`.
- **Why still remaining:** No reliable source-of-truth evidence in current Laravel schema/doc mapping for stronger approval side effects such as extra accounting or multi-step authorization.
- **Risk:** **Low to Medium**.

## 4. Guest department linkage uses department-name matching, not foreign keys
- **Blocker:** `guests.department` is free text, not `department_id`.
- **Why not fixed:** Schema migration would widen scope and require UI/form/data migration proof beyond current prompt window.
- **Risk:** **Medium**. Chargeback posting works only when guest department text matches a real department name.

## 5. Special costing / mess costing subsystem parity remains unproven
- **What was checked:** Flask truth-pack surfaces in `templates/mess_costing_bill_system.html` and `tests/test_financial_reporting_alignment.py`.
- **Blocker:** Laravel repo contains accounting and department-ledger surfaces, but no distinct proven executable counterpart for the full Flask costing subsystem was conclusively identified in this branch.
- **Why not fixed:** Insufficient direct executable mapping from truth pack to Laravel schema/service contract.
- **Risk:** **Medium** if that subsystem is in final parity scope.

## 6. Full suite remains warning-heavy / not clean-pass proven
- **Observed result:** `php artisan test` currently ends with `12 warnings, 5 passed (83 assertions)` in this local pass, not a clean all-green suite.
- **Why this matters:** Even though the targeted repairs now hold, the branch still lacks a clean full-suite proof baseline.
- **Risk:** **High** for deployment readiness.
