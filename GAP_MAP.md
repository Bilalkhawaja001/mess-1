# Kitchen Inventory & Consumption — GAP_MAP

Baseline: `CURRENT_MODULE_TRUTH.md` (actual Laravel code on branch `repair/full-parity-fix`).  
Target (from task brief):
- Item **base unit + multiple transaction units with conversion to base unit**.
- **Kitchen/mess traceability on issues.**
- **Consumption/wastage/damage/expired outward movements.**
- **Low-stock/reorder reporting.**
- **Procurement-to-consumption traceability.**
- Preserve: **GRN create as only inward stock posting** and **no double-post on approval**.
- Keep UI simple; audit-first.

---

## 1. Units & Conversions

**Current truth**
- Single `items.uom` string; treated as implicit base unit.
- `stock_transactions.quantity` stored "as-is" in that unit.
- No concept of **transaction unit** vs **base unit**.
- No `uom_conversion` / `item_units` table.
- All UIs (inventory manual txn, kitchen issue, GRN) assume one unit per item.

**Target behavior**
- Each item has a **base unit** (e.g., `kg`, `ltr`, `piece`).
- Multiple **transaction units** per item (e.g., `bag`, `crate`, `dozen`) with a deterministic **conversion factor to the base unit**.
- Each stock movement (GRN, kitchen issue, manual txn) may be recorded in a transaction unit, but the **ledger is stored in base units**.

**Gaps**
1. Schema has no storage for per-item conversion rules (no child table for item units).
2. `stock_transactions` has no columns to store **transaction unit** / **transaction quantity** separately from base quantity.
3. Controllers (`InventoryController@storeTxn`, `ProcurementController@storeGrn`, `KitchenController@issue`) do not:
   - Offer a choice of unit.
   - Convert from chosen unit to base.
4. UI has no surfaces to manage per-item units or to show transaction-unit context.

**Implication**
- Cannot safely support multiple purchase/issue units per item while keeping a consistent base inventory ledger.

---

## 2. Kitchen / Mess Traceability on Issues

**Current truth**
- `kitchen_issues` table: `issue_date`, `item_id`, `quantity`, `remarks`.
- No `mess_id` / `department_id` or equivalent.
- `StockTransaction` from kitchen issue only links back to `KitchenIssue` via `reference_type`/`reference_id`.
- `messes` exist and are used for members & accounting, but are **not referenced** from kitchen issues.

**Target behavior**
- Each kitchen issue should be traceable to **which mess / kitchen** raised it.
- Downstream analysis should answer: *"For mess X, how much of item Y was issued for consumption / wastage / damage / expired?"*

**Gaps**
1. Schema: `kitchen_issues` lacks `mess_id` foreign key.
2. Kitchen UI has no mess selector or filter in the issue form.
3. Controllers do not accept or validate mess context for issues.
4. Export/report has no grouping by mess for kitchen-driven stock-outs.

---

## 3. Outward Movement Types (Consumption / Wastage / Damage / Expired)

**Current truth**
- `kitchen_issues.quantity` is a scalar with no classification.
- `StockTransaction.txn_type` uses `KITCHEN_ISSUE` for all kitchen issues.
- Manual inventory transactions can use `OUT`/`ADJUSTMENT`, but there is no structured reason field.

**Target behavior**
- Kitchen issues must distinguish:
  - **CONSUMPTION** — normal usage against meals.
  - **WASTAGE** — spoilage/spillage.
  - **DAMAGE** — non-usable due to handling/storage damage.
  - **EXPIRED** — expired stock.
- Outward movements should be reportable per reason type.

**Gaps**
1. `kitchen_issues` has no `issue_type` / reason column.
2. Stock transactions do not carry reason metadata beyond generic `txn_type = KITCHEN_ISSUE`.
3. UI does not capture or display any reason breakdown.

---

## 4. Low-Stock / Reorder Reporting

**Current truth**
- `items.reorder_level` exists and is populated via forms/CSV.
- `InventoryService::stockBalances()` can compute per-item balances.
- Inventory Blade currently only shows:
  - Item grid with `reorder_level` column.
  - No explicit low-stock highlighting or report section.
  - Ledger data is prepared in controller but omitted from the view.

**Target behavior**
- Simple, admin-first **low-stock view** that highlights items where stock is at or below `reorder_level`.
- Ideally, reuse existing balance computation; no complex forecasting.

**Gaps**
1. No dedicated low-stock collection in controller (just bare balances array).
2. No UI component (card/table) listing low-stock items.
3. No quick visual indicator (badge/color) on the main items list.

---

## 5. Procurement-to-Consumption Traceability

**Current truth**
- Procurement side:
  - Stock-in from GRN → `StockTransaction` with `txn_type = 'GRN'`, referencing `GoodsReceipt`.
  - From GRN we can navigate to PO and vendor; so inbound chain is: **Vendor → PO → GRN → StockTransaction**.
- Kitchen side:
  - Stock-out from kitchen → `StockTransaction` with `txn_type = 'KITCHEN_ISSUE'`, referencing `KitchenIssue`.
- There is **no direct correlation layer** between GRN batches and specific issues/consumption.
- No report surfaces that show side-by-side **purchased vs consumed** quantities/value per item, per period, per mess.

**Target behavior (minimal)**
- Ability to **trace an item** across:
  - What was received (GRN qty & cost).
  - What was issued/consumed (kitchen issues) — ideally per mess and issue type.
- At minimum, an item-level **trail view** that slices all stock transactions for that item by role:
  - Inward: GRN/OPENING/IN.
  - Outward: KITCHEN_ISSUE/OUT.

**Gaps**
1. Schema does not block traceability (links already exist), but **no coherent service or UI** exposes this chain.
2. No date-range filtered procurement/consumption report for a given item.
3. No linkage to mess context (until `mess_id` is added to kitchen issues).

---

## 6. Approval Semantics & Posting Rules (Must Remain Truthful)

**Current guarantees**
- **GRN create posts stock**; `approveGrn` is audit-only.
- **Kitchen issue create posts stock-out**; `approveIssue` is audit-only.
- `LOGIC_RESULT_VERIFICATION.md` and `docs/repair/30_P1_FIX_PROOF.md` explicitly state:
  - No fake or duplicate postings should be invented on approvals.

**Target constraints**
- New unit/mess/reason/reporting features **must not** introduce any second posting on approvals.
- All new flows must keep these invariants explicit in messages and tests.

**Gaps to cover in implementation**
1. Tests currently assert approval messages & non-posting behavior but do **not** assert any unit/mess metadata.
2. Any new columns/tests must preserve:
   - Exactly one stock transaction per GRN line.
   - Exactly one stock transaction per kitchen issue.
   - Approvals only update status/timestamps.

---

## 7. Scope Guardrails (What We Intentionally Do NOT Touch)

To stay within "minimal, clean" scope and avoid redesigning unrelated areas, the following are **explicitly out-of-scope** for this task:

1. Deep department ledger & COA/JV rules — existing accounting remains as-is.
2. Month closure, billing, and member ledger flows.
3. Guest meals costing logic beyond their existing ledger integration.
4. Any new long-running background jobs or scheduled recomputations.

We will **only**:
- Extend schema minimally around inventory, procurement, kitchen issue, and mess linkage.
- Extend services/controllers/views to satisfy the gaps above.
- Add focused tests to lock in the new behavior while preserving current posting invariants.
