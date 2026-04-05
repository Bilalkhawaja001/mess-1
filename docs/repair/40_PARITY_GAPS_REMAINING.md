# 40 PARITY GAPS REMAINING

This file lists only real remaining gaps after the latest pushed repair pass.

## 1. Monthly billing department/journal parity
- **Classification:** `BLOCKING`
- **Current state:** Member-ledger truth for billing generation/correction is materially improved, but standard monthly billing still does not have a proven department-ledger/journal posting contract in current Laravel code/schema.
- **Why still open:** Flask truth-pack does not provide a sufficiently executable mapping into the current Laravel schema for honest implementation.
- **Why blocking:** Financial downstream parity is still unproven for live deployment scope.

## 2. Kitchen approval semantics
- **Classification:** `BLOCKING`
- **Current state:** Approval endpoints are explicit-safe and no longer misleading, but they are still not full-parity proven workflows.
- **Why still open:** Flask truth-pack shows approval surfaces, but not enough executable side-effect detail to justify inventing accounting/stock approval behavior.
- **Why blocking:** Live users could still assume approval implies a deeper operational completion than current code truly performs.

## 3. Procurement approval semantics
- **Classification:** `BLOCKING`
- **Current state:** PO approval is mainly a status transition; GRN approval acknowledges stock was already posted at GRN creation.
- **Why still open:** No proven stronger approval contract exists in current schema/truth-pack mapping.
- **Why blocking:** Deployment-safe parity is still not fully demonstrated for procurement approvals.

## 4. Guest department linkage by free-text department name
- **Classification:** `BLOCKING`
- **Current state:** Guest meal chargeback still depends on matching `guests.department` text to a real department name.
- **Why still open:** No FK-backed department linkage exists for guests in current schema.
- **Why blocking:** Silent mis-posting risk remains if department text is inconsistent.

## 5. Special costing subsystem parity
- **Classification:** `DEFERRED_BY_SCOPE`
- **Current state:** Flask truth-pack contains costing/report templates, but no conclusive executable Laravel target was identified for this repair pass.
- **Why deferred:** Current repair mission was centered on verified live blockers already present on the repair branch; special costing remains unproven but not concretely mapped enough for honest implementation.
- **Deployment effect:** still contributes to NO-GO because deployment scope itself remains ambiguous.

## 6. Full test baseline cleanliness
- **Classification:** `BLOCKING`
- **Current state:** focused repair suite completes without failing assertions, but full `php artisan test` still returns warning-heavy output instead of a clean baseline.
- **Why still open:** warning sources are not reduced to a deployment-clean state in this pass.
- **Why blocking:** cPanel-safe deployment proof is still insufficient.
