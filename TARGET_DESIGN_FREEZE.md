# Kitchen Inventory & Consumption — TARGET_DESIGN_FREEZE

Design is frozen for this task. Implementation must match this document and must preserve existing posting invariants.

---

## 1. Units & Conversions Model

### 1.1. Item-level Base Unit

- The existing `items.uom` column is treated as the **base unit** for that item.
- All inventory balances remain expressed in this base unit.
- We do **not** rename the column to avoid churn; we only clarify semantics.

### 1.2. Transaction Units per Item

New table: `item_units`

- Columns:
  - `id` (PK)
  - `item_id` (FK → `items.id`, cascade on delete)
  - `unit_code` (string, 20) — e.g. `kg`, `bag`, `crate`, `dozen`.
  - `factor_to_base` (decimal(12,4)) — multiplicative factor such that:
    - `base_quantity = transaction_quantity * factor_to_base`.
  - `is_default_for_grn` (bool, default false).
  - `is_default_for_kitchen` (bool, default false).
  - timestamps.

Rules:
- At least one `item_units` row **must** exist per item — the **base unit row**:
  - `unit_code = items.uom`.
  - `factor_to_base = 1.0`.
- For simplicity, additional units are optional and manageable via CSV/import only in this iteration (no complex UI needed beyond a basic overview).

### 1.3. Stock Transactions: Raw vs Base Quantity

Extend `stock_transactions` with:

- `trans_unit_code` (nullable string, 20) — the unit chosen for this transaction.
- `trans_quantity` (nullable decimal(12,3)) — quantity in `trans_unit_code`.

Semantics:
- `quantity` (existing) becomes **base quantity** in `items.uom`.
- If `trans_unit_code` / `trans_quantity` are null, the transaction is interpreted as already in base unit (backwards compatible).
- When trans fields are present:
  - `quantity = trans_quantity * factor_to_base` (resolved from `item_units` for (`item_id`, `trans_unit_code`)).

Controllers must always persist `quantity` in base units. UI may show transaction unit and quantity if present.

---

## 2. Kitchen Issues: Mess & Reason

### 2.1. Schema Changes

Extend `kitchen_issues` with:

- `mess_id` (nullable FK → `messes.id`, `nullOnDelete`).
- `issue_type` (string, 20) — constrained at application layer to one of:
  - `CONSUMPTION`
  - `WASTAGE`
  - `DAMAGE`
  - `EXPIRED`

Defaults:
- Existing rows will have `mess_id = null` and `issue_type = 'CONSUMPTION'` via migration defaults.

### 2.2. Controller Behavior

`KitchenController@issue`:

- Validation:
  - `issue_date` — required date.
  - `item_id` — required, exists in `items`.
  - `quantity` — required numeric, min 0.001 (still in **transaction unit**, see Units section).
  - `issue_type` — required, `in:CONSUMPTION,WASTAGE,DAMAGE,EXPIRED`.
  - `mess_id` — nullable, `exists:messes,id`.
- Behavior:
  1. Resolve transaction unit & factor (see §3) and compute base quantity.
  2. Create `KitchenIssue` with `issue_date`, `item_id`, `quantity` (stored in **base unit**), `issue_type`, `mess_id`, `remarks`.
  3. Create a single `StockTransaction` row:
     - `item_id` — as provided.
     - `txn_type` — still `KITCHEN_ISSUE` (no change to semantic type).
     - `trans_unit_code` / `trans_quantity` — if a non-base transaction unit is used.
     - `quantity` — base-unit quantity.
     - `unit_cost` — remain 0 for now.
     - `reference_type` = `KitchenIssue::class`.
     - `reference_id` = new issue id.
     - `txn_at` = `issue_date`.
     - `remarks` — include issue type and mess summary for quick scanning, e.g. `"Kitchen issue (CONSUMPTION, Mess: MAIN)"`.
- `approveIssue` stays **non-posting** (no stock changes) and continues to `touch()` the issue only.

### 2.3. UI Changes

On `admin/kitchen/index.blade.php`:

- Post Kitchen Issue form gains:
  - **Issue Type** dropdown (CONSUMPTION/WASTAGE/DAMAGE/EXPIRED).
  - **Mess** dropdown (optional) using `messes` available in the system.
  - Unit selection as per §3.
- Issues table shows new columns:
  - `Mess` (name or `—` if none).
  - `Issue Type`.

---

## 3. Unit Handling in UI & Controllers

### 3.1. Unit Resolution Rules

For this iteration, we keep UI minimal:

- Inventory manual transactions (`InventoryController@storeTxn`):
  - Keep a **simple unit select**:
    - Default to the item base unit.
    - Optionally list extra `item_units` entries.
  - Store:
    - `trans_unit_code` & `trans_quantity` if non-base selected.
    - `quantity` computed in base.

- Procurement GRN (`ProcurementController@storeGrn`):
  - Add **unit selection per GRN line**:
    - Default: PO line uses base unit; GRN defaults to that base unit.
    - If `item_units` contain other units, allow switching.
  - Compute base quantity for `StockTransaction.quantity` using `factor_to_base`.
  - Store chosen `trans_unit_code` / `trans_quantity`.

- Kitchen Issue (`KitchenController@issue`):
  - Add **unit selection** aligned with item units.
  - Same conversion rules as above.

If no item_units row for a given `unit_code` is found:
- Controller must return a validation error: **"Invalid unit for item"**, preventing inconsistent posting.

### 3.2. InventoryService Compatibility

- `InventoryService::balanceForItem` continues to sum **`quantity` (base-unit)**, unaffected by the introduction of `trans_*` fields.
- No change to the public service contract; only the write path is extended.

---

## 4. Low-Stock / Reorder Reporting

### 4.1. Controller

Extend `InventoryController@index` to compute:

- `$balances` — as today.
- `$lowStockItems` — collection of items where:
  - `is_active = true`, and
  - `reorder_level > 0`, and
  - `current_balance <= reorder_level` (using `InventoryService::balanceForItem`).

### 4.2. UI

On `admin/inventory/index.blade.php`:

- Add a **Low Stock** card above the items list:
  - Shows count of low-stock items.
  - Displays a small table:
    - Columns: ItemCode, ItemName, Balance (base unit), Reorder Level.
- In the main items table:
  - If an item is low-stock, highlight `Reorder` cell (e.g. red badge) and optionally show `Low` tag.

No advanced date ranges or forecasting — purely current balance vs threshold.

---

## 5. Procurement-to-Consumption Trail

### 5.1. Service

Extend `InventoryService` with a read-only method:

- `procurementToConsumptionTrail(int $itemId): array`

Behavior:
- Returns a structured array with two groups:
  - `inward`: list of GRN-backed stock transactions for the item:
    - Fields: `txn_at`, `quantity` (base), `trans_unit_code`, `trans_quantity`, `unit_cost`, `grn_number`, `po_number`, `vendor_name`.
  - `outward`: list of kitchen-issue-backed stock transactions for the item:
    - Fields: `txn_at`, `quantity` (base), `trans_unit_code`, `trans_quantity`, `issue_type`, `mess_name` (if any), `remarks`.
- Implementation leverages:
  - StockTransaction records filtered by `item_id`.
  - Joins through `reference_type`/`reference_id` to `GoodsReceipt` / `KitchenIssue`, then to `PurchaseOrder` & `Vendor`, and `Mess`.

### 5.2. Route & Controller Action

New route under `permission:inventory.manage`:

- `GET /admin/inventory/items/{item}/trail` → `InventoryController@trail`.

`trail(Item $item)`:
- Calls `InventoryService::procurementToConsumptionTrail($item->id)`.
- Renders `resources/views/admin/inventory/trail.blade.php` with:
  - Header summarizing item (code, name, base unit).
  - Two simple tables: **Procurement** (GRNs) and **Kitchen Issues**.

### 5.3. UI Constraints

- No pagination or heavy filters; limited to a reasonable number of rows (e.g. latest 200 inwards and 200 outwards).
- Focus is on providing a **human-readable audit path** from vendor purchase to kitchen consumption by item.

---

## 6. Posting Invariants (Audit-First Rules)

These rules are **frozen** and must be respected in all code and tests:

1. **GRN create is the only stock-in posting for procurement.**
   - Exactly one `StockTransaction` per GRN line.
   - `approveGrn` must **not** create, update, or delete stock transactions; it may only touch audit timestamps and PO status.

2. **Kitchen issue create is the only stock-out posting for kitchen.**
   - Exactly one `StockTransaction` per `KitchenIssue` record.
   - `approveIssue` must **not** create, update, or delete stock transactions; it may only touch timestamps.

3. **Base-unit ledger.**
   - `stock_transactions.quantity` is always stored in the item’s base unit (`items.uom`).
   - All stock balance and low-stock computations use the base quantity.

4. **Backwards compatibility.**
   - Existing stock transactions without `trans_unit_code` / `trans_quantity` are treated as base-unit transactions.
   - Existing kitchen issues and GRNs remain valid; new optional metadata does not break them.

---

## 7. Test Coverage Expectations

New tests must:

1. Verify **unit conversion** behavior:
   - Creating GRN / kitchen issues with a non-base transaction unit correctly computes base `quantity`.
   - Inventory balances respect converted quantities.

2. Verify **mess and reason tagging**:
   - Kitchen issues store `mess_id` and `issue_type` and surface them in the UI.
   - Outward entries in the trail reflect these fields.

3. Verify **low-stock reporting**:
   - Items below (or equal to) `reorder_level` appear in `lowStockItems` and are highlighted.

4. Re-assert **posting invariants**:
   - No second stock posting on GRN approval or kitchen issue approval.
   - Exactly one stock transaction per GRN line and per kitchen issue.

All new tests should prefer **small, focused feature tests** under `tests/Feature` reusing `RefreshDatabase` and existing helper patterns.

---

This design is now frozen for the scope of this task. Any deviation must be reflected by updating this file first and keeping implementation + tests aligned.
