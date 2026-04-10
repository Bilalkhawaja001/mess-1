# Kitchen Inventory & Consumption — CURRENT_MODULE_TRUTH

_Source of truth: actual Laravel code in `app/`, `database/migrations`, `resources/views`, `routes/web.php`, and tests as of branch `repair/full-parity-fix`._

## 1. Inventory Model (Items & Stock Transactions)

**Tables**
- `items`
  - `id`
  - `name` (string)
  - `sku` (unique string, used as ItemCode)
  - `uom` (string, 20) — single unit of measure per item (treated as the base unit in all arithmetic)
  - `reorder_level` (decimal(12,3), default 0)
  - `is_active` (bool, default true)
  - timestamps

- `stock_transactions`
  - `id`
  - `item_id` → `items.id`
  - `txn_type` (string, 40)
  - `quantity` (decimal(12,3)) — interpreted as **quantity in the item’s `uom`**
  - `unit_cost` (decimal(12,2), default 0)
  - `reference_type` (nullable string, 80) — polymorphic reference
  - `reference_id` (nullable unsigned big int)
  - `remarks` (nullable text)
  - `txn_at` (timestamp)
  - timestamps

**Service logic**
- `App\Services\InventoryService`
  - `balanceForItem(int $itemId): float`
    - Aggregates **inward** quantities where `txn_type` in `['OPENING','IN','ADJUSTMENT','GRN']`.
    - Aggregates **outward** quantities where `txn_type` in `['OUT','KITCHEN_ISSUE']`.
    - Returns `round(in - out, 3)` as the current stock balance in the item’s `uom`.
  - `stockBalances(): array`
    - Returns `['item' => Item, 'balance' => float]` rows for all items.

**InventoryController@index**
- Loads:
  - `$items` — all items ordered by name.
  - `$ledger` — last 100 `StockTransaction` records ordered by `txn_at` desc.
  - `$balances` via `InventoryService::stockBalances()`.
- Renders `resources/views/admin/inventory/index.blade.php`.

**Item Create / Import**
- Manual create (`storeItem`):
  - Accepts `item_code`, `item_name`, `category`, `uom`, `reorder_level` (+ legacy `sku`, `name`).
  - Requires non-empty ItemCode & ItemName.
  - Creates `Item` with:
    - `sku = item_code`
    - `name = item_name`
    - `category` (default `Uncategorized`), `uom`, `reorder_level` (default 0), `is_active = true`.
- Bulk upload (`bulkUploadItems`):
  - CSV with headers `ItemCode, ItemName, Category, UoM` (case-insensitive).
  - Validates each row; any row missing ItemCode/ItemName/UoM aborts with an error mentioning line no.
  - Uses `Item::upsert(rows, ['sku'], ['name','category','uom','is_active','updated_at'])`.
- Legacy import (`importItems`):
  - CSV with headers like `name,sku,uom,reorder_level,is_active,category` (case-insensitive).
  - For each valid row (non-empty name/sku/uom), either updates existing item by `sku` or inserts new.

**Stock transactions (manual)**
- `InventoryController@storeTxn`:
  - Validates `item_id`, `txn_type` in `OPENING,IN,OUT,ADJUSTMENT`, `quantity`, `txn_at`.
  - Creates `StockTransaction` with:
    - `item_id`, `txn_type`, `quantity` (as passed), `unit_cost` (default 0), `remarks`, `txn_at`.
  - No UI extra metadata — pure manual ledger entry in item’s `uom`.

## 2. Procurement Module (PO & GRN → Stock)

**Tables**
- `vendors`: basic master (name + contact details + `is_active`).
- `purchase_orders`:
  - `id`, `vendor_id` → `vendors.id`
  - `po_number` (unique, auto `PO-YYYYMMDDHHMMSS` on create)
  - `po_date`
  - `status` (`DRAFT` default, but controller uses `ISSUED` on create; later `APPROVED` / `PARTIALLY_RECEIVED` / `RECEIVED`)
  - `remarks`
- `purchase_order_lines`:
  - `purchase_order_id` → `purchase_orders.id`
  - `item_id` → `items.id`
  - `qty_ordered` (decimal)
  - `unit_price` (decimal)
- `goods_receipts`:
  - `id`, `purchase_order_id` → `purchase_orders.id`
  - `grn_number` (unique, auto `GRN-YYYYMMDDHHMMSS` on create)
  - `received_date`
  - `remarks`
- `goods_receipt_lines`:
  - `goods_receipt_id` → `goods_receipts.id`
  - `item_id` → `items.id`
  - `qty_received` (decimal)
  - `unit_cost` (decimal)

**ProcurementController@index**
- Loads:
  - `$vendors` — all vendors.
  - `$items` — active items ordered by `sku`.
  - `$pos` — last 50 POs with vendors, lines & GRNs fully eager-loaded and **augmented**:
    - For each PO:
      - `total_qty` = sum of `qty_ordered`.
      - `received_qty` = sum of all `qty_received` on linked GRNs.
      - `pending_qty` = `max(total_qty - received_qty, 0)`.
      - Each line gets `received_qty` & `pending_qty` for that item.
  - `$grns` — last 50 GRNs with PO/vendor/lines/item.
- Renders `resources/views/admin/procurement/index.blade.php` (vendor/PO/GRN UI with inline JS validation hints).

**PO create / approve**
- `storePo`:
  - Validates vendor, date, and line array (items, qty_ordered > 0, unit_price > 0).
  - Rejects if all lines are empty/invalid or if duplicate `item_id` in the same PO.
  - Creates PO (`status = ISSUED`) and PO lines inside a transaction.
- `approvePo`:
  - Sets `status = APPROVED`.
  - **No accounting / stock posting** — message explicitly says: *"Current schema has no deeper approval posting beyond status transition."*

**GRN create / approve**
- `storeGrn`:
  - Validates PO, specific PO line, item, received_date, qty_received, unit_cost.
  - Enforces:
    - Selected line must exist in PO.
    - Selected item must match the PO line’s item.
    - Previously received quantity for that item on that PO line is computed from existing GRNs.
    - Rejects if fully received or if `qty_received` exceeds pending.
  - In a DB transaction (with `lockForUpdate` on the PO):
    - Re-validates the same constraints.
    - Creates `GoodsReceipt`.
    - Creates `GoodsReceiptLine`.
    - Creates **one StockTransaction**:
      - `item_id` = item from PO line
      - `txn_type` = `GRN`
      - `quantity` = `qty_received` (in the item’s `uom`)
      - `unit_cost` = provided `unit_cost`
      - `reference_type` = `GoodsReceipt::class`
      - `reference_id` = new GRN id
      - `txn_at` = `received_date`
      - `remarks` = `"GRN posting (stock posted on create)"`
    - Recomputes total ordered vs received qty across PO, and updates PO `status`:
      - `< total` → `PARTIALLY_RECEIVED`
      - `>= total` → `RECEIVED`.
  - Returns with success message: **"GRN posted"**.

- `approveGrn`:
  - Recomputes PO-level ordered vs received quantities and updates PO status to `PARTIALLY_RECEIVED` / `RECEIVED`.
  - Calls `$grn->touch()` (audit-only).
  - Returns with success message explicitly stating: *"Stock was already posted on GRN create; no extra approval side-effect exists in current schema."*

> **Truth:** **GRN create is the single source of stock-inward posting for procurement. GRN approval is a no-op for stock; it only touches timestamps and PO status.**

## 3. Kitchen / Meal Planning / Issues

**Tables**
- `menus` — name, `meal_type`, `is_active`.
- `recipes` — `menu_id`, `item_id`, `qty_per_serving`.
- `meal_plans` — `plan_date`, `menu_id`, `planned_servings`.
- `kitchen_issues` — `issue_date`, `item_id`, `quantity`, `remarks`.

**KitchenController@index**
- Loads:
  - `$items` — all items.
  - `$menus` — latest menus.
  - `$recipes` — 200 latest recipes.
  - `$plans` — 200 latest meal plans.
  - `$issues` — 200 latest kitchen issues.
  - `$consumption` — aggregated `sum(quantity)` by `item_id` across `kitchen_issues`.
- Renders `resources/views/admin/kitchen/index.blade.php`:
  - Simple CRUD for menus, recipes, meal plans.
  - **Post Kitchen Issue** form: date, item, quantity, remarks.
  - Table of issues with an **Approve** button per row.

**Meal planning**
- `storeMenu`, `updateMenu`, `deleteMenu` — basic menu CRUD.
- `storeRecipe`, `updateRecipe`, `deleteRecipe` — each recipe row links a menu + item + qty-per-serving.
- `storePlan`, `updatePlan` — create/update meal plan per date and menu.
- `approvePlan(MealPlan $plan)`:
  - Just `touch()` the plan.
  - Success message: *"Meal plan approval acknowledged. No inventory/accounting side-effect exists in current schema."*
  - No stock or accounting entries.

**Kitchen issue → Stock out**
- `issue(Request $request)`:
  - Validates `issue_date`, `item_id`, `quantity`.
  - Creates `KitchenIssue` row with those fields + `remarks`.
  - Immediately creates one `StockTransaction`:
    - `item_id` from request
    - `txn_type` = `KITCHEN_ISSUE`
    - `quantity` = requested quantity (interpreted in `items.uom`)
    - `unit_cost` = 0 (no costing logic yet)
    - `reference_type` = `KitchenIssue::class`
    - `reference_id` = issue id
    - `txn_at` = `issue_date`
    - `remarks` = request remarks or default `"Kitchen issue"`
  - Success message: **"Kitchen issue posted"**.

- `approveIssue(KitchenIssue $issue)`:
  - Calls `$issue->touch()`.
  - Returns success message: *"Kitchen issue approval acknowledged. Stock was already posted on issue create; no extra approval side-effect exists in current schema."*

> **Truth:** **Kitchen issue create is the single source of stock-out posting for kitchen consumption. Approval does not change stock.**

## 4. Mess / Department Context

**Tables**
- `departments` — master (`name`, `code`, `is_active`).
- `messes` — mess master:
  - `name`, `code` (unique)
  - `department_id` → `departments.id` nullable
  - `is_active` bool
- `department_ledgers` — department/mess accounting entries:
  - `department_id` → `departments.id`
  - `mess_id` → `messes.id` nullable
  - `entry_date`, `entry_type` (`DEBIT`/`CREDIT`), `amount`
  - `reference_type`, `reference_id`, `remarks`

**Controllers**
- `AccountingController@index`:
  - Pulls departments, messes, latest 100 ledger entries and simple `net_cost` per department.
  - Renders `admin/accounting/index` with a note: *"Module operational."*
- Guest meals approval flows (in `GuestController`) already create department ledger entries referencing `GuestMeal` with a linked `mess_id` (see `RepairFinancialFlowsTest::test_guest_approval_creates_department_chargeback_entry`).

**Kitchen / inventory today**
- **KitchenIssue** has **no `mess_id` field** and no issue type classification.
- Stock transactions created from kitchen issues therefore have no explicit mess/department linkage; they only link back to `KitchenIssue`.
- Procurement GRN stock transactions have no mess or department linkage; they only link back to `GoodsReceipt` and through that to `PurchaseOrder` & `Vendor`.

## 5. Current UI & Reporting

- Inventory page:
  - Item creation and CSV imports.
  - Item list with `sku`, `name`, `category`, `uom`, `reorder_level`, `is_active`.
  - No dedicated **low-stock/reorder** view; `reorder_level` is stored but not surfaced as a report.
  - Ledger snippet (last 100 stock txns) is available in controller but **not yet rendered** in the Blade template.
- Kitchen page:
  - Simple admin-first UI for menus, recipes, plans, and issues.
  - No direct visibility of stock balances, GRN history, or cost/consumption analytics.
- Export center (`ExportCenterController`):
  - `stockLedger()` endpoint exports **all** `StockTransaction` rows as CSV (`txn_at,item_id,txn_type,quantity,unit_cost,remarks`).
  - No built-in aggregation by mess, kitchen issue type, or procurement chain.

## 6. Locked Truths Relevant to This Task

From the existing code and repair docs (`LOGIC_RESULT_VERIFICATION.md`, `docs/repair/*`):

1. **GRN create is the only inward stock posting for procurement.**
   - Implemented via `StockTransaction::create([... 'txn_type' => 'GRN', ...])` in `storeGrn`.
   - `approveGrn` must remain non-posting.

2. **Kitchen issue create is the only outward stock posting for kitchen consumption.**
   - Implemented via `StockTransaction::create([... 'txn_type' => 'KITCHEN_ISSUE', ...])` in `KitchenController@issue`.
   - `approveIssue` must remain non-posting.

3. **Inventory balances are computed purely from `stock_transactions` using `txn_type` groups and the item’s `uom` as a single unit.**

4. **Mess / department accounting is present but not wired into kitchen inventory flows.**
   - Guest meals feed `department_ledgers` with a `mess_id`.
   - Kitchen issues and GRNs do **not** feed `department_ledgers` or carry `mess_id` today.

5. **There is no native multi-unit / conversion model today.**
   - `items.uom` is a single text field; all quantities are assumed to be in that unit.
   - There is no `uom_conversion` table or per-transaction unit metadata.

This document reflects the real, verifiable behavior of the current kitchen/inventory/procurement stack and is the baseline for gap mapping and target design.
