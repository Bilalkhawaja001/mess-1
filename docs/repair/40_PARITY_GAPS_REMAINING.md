# 40 PARITY GAPS REMAINING

This file lists only real remaining gaps after the repair branch changes.

## 1. Billing department/journal parity for normal monthly billing
- **Blocker:** Current Laravel schema/services do not expose a proven department-ledger or journal posting contract for standard member billing generation/correction beyond member ledger.
- **Why not fixed:** Truth pack available in workspace did not provide an executable, unambiguous mapping for monthly billing department/journal posting into the current Laravel schema.
- **Risk:** **High** if Flask truth expects downstream departmental accounting from every monthly billing run.

## 2. ReportController month recovery rows still use legacy payment status
- **Blocker:** `app/Http/Controllers/Admin/ReportController.php` uses `status='APPROVED'` when summing paid amounts in `index()`.
- **Why not fixed in this pass:** P0 financial posting + verification work was prioritized first. Overall recovery page already reads ledger truth, but this older month view still contains legacy status logic.
- **Risk:** **Medium**. Some report screens can understate paid amounts under new payment lifecycle statuses.

## 3. Kitchen approval semantics remain workflow-shell only
- **Blocker:** `app/Http/Controllers/Admin/KitchenController.php::approvePlan()` and `approveIssue()` still use `touch()` only.
- **Why not fixed:** Truth pack did not give enough executable accounting/stock side-effect spec to justify inventing approval behavior.
- **Risk:** **Medium**. Operational parity remains incomplete; approval surface can still overstate completion.

## 4. Procurement approval semantics are shallow
- **Blocker:** PO/GRN approval mostly changes status; GRN create posts stock already, but approval semantics are still thin relative to a fuller audited workflow.
- **Why not fixed:** No reliable source-of-truth evidence in current Laravel schema/doc mapping for stronger side effects.
- **Risk:** **Low to Medium**.

## 5. Guest department linkage uses department-name matching, not foreign keys
- **Blocker:** `guests.department` is free text, not `department_id`.
- **Why not fixed:** Schema migration would widen scope and require UI/form/data migration proof beyond current prompt window.
- **Risk:** **Medium**. Chargeback posting works only when guest department text matches a real department name.

## 6. Special costing / mess costing subsystem parity remains unproven
- **Blocker:** Accounting and department-ledger surfaces exist, but no distinct proven Flask subsystem implementation target was conclusively identified in the Laravel repo.
- **Why not fixed:** Insufficient direct executable mapping from truth pack to Laravel schema/service contract.
- **Risk:** **Medium** if that subsystem is in final parity scope.
