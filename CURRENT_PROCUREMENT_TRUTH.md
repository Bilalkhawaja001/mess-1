# CURRENT PROCUREMENT TRUTH

## Routes
- `GET /admin/procurement` → `App\Http\Controllers\Admin\ProcurementController@index`
- `POST /admin/procurement/vendors` → `storeVendor`
- `POST /admin/procurement/po` → `storePo`
- `POST /admin/procurement/po/{po}/approve` → `approvePo`
- `POST /admin/procurement/grn` → `storeGrn`
- `POST /admin/procurement/grn/{grn}/approve` → `approveGrn`
- Route group is protected by `permission:procurement.manage` in `routes/web.php`.

## Controller / service truth
- All procurement logic is currently concentrated in `app/Http/Controllers/Admin/ProcurementController.php`.
- There is no separate procurement service/action layer in the current repo truth.
- `index()` computes display-only totals/pending values in-memory by eager-loading:
  - `vendor`
  - `lines.item`
  - `goodsReceipts.lines`
- `storeVendor()` only validates `name` as required.
- `storePo()` currently validates:
  - `vendor_id` required + exists
  - `po_date` required + date
  - `lines` required array min 1
  - `lines.*.item_id` required + exists
  - `lines.*.qty_ordered` required numeric min 0.001
  - `lines.*.unit_price` nullable numeric min 0
- `storePo()` also rejects:
  - empty filtered line payload
  - duplicate `item_id` within one PO
- `approvePo()` only updates `status = APPROVED`
- `storeGrn()` currently validates:
  - `purchase_order_id` required + exists
  - `purchase_order_line_id` required + exists
  - `item_id` required + exists
  - `received_date` required + date
  - `qty_received` required numeric min 0.001
  - `unit_cost` is **not validated**; it is read from request and defaults to `0`
- `storeGrn()` currently performs:
  - PO + line consistency check
  - item/line consistency check
  - pending check against selected PO line
  - creates GRN
  - creates GRN line
  - creates stock transaction with `txn_type = GRN`
  - recomputes PO status using first line ordered qty vs total received qty
- `approveGrn()` currently:
  - sets parent PO status to `RECEIVED`
  - touches GRN
  - does not post stock

## Models / tables
### Models used
- `Vendor`
- `Item`
- `PurchaseOrder`
- `PurchaseOrderLine`
- `GoodsReceipt`
- `GoodsReceiptLine`
- `StockTransaction`

### Table truth from migration
Defined in `database/migrations/2026_03_18_130000_create_missing_functional_modules_tables.php`:
- `vendors`
  - `id`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, timestamps
- `purchase_orders`
  - `id`, `vendor_id`, `po_number`, `po_date`, `status`, `remarks`, timestamps
- `purchase_order_lines`
  - `id`, `purchase_order_id`, `item_id`, `qty_ordered`, `unit_price`, timestamps
- `goods_receipts`
  - `id`, `purchase_order_id`, `grn_number`, `received_date`, `remarks`, timestamps
- `goods_receipt_lines`
  - `id`, `goods_receipt_id`, `item_id`, `qty_received`, `unit_cost`, timestamps
- `stock_transactions`
  - used by controller; schema not audited in full here, but model fillable supports `item_id`, `txn_type`, `quantity`, `unit_cost`, `reference_type`, `reference_id`, `remarks`, `txn_at`

## Blade / frontend truth
- Single page view: `resources/views/admin/procurement/index.blade.php`
- Current UI sections:
  - Create Vendor form
  - Create PO form with dynamic multiple line items
  - Create GRN form with PO→line selection
  - Purchase Orders list/table
  - GRNs list/table
- JS behavior present in the same blade:
  - searchable item picker for PO lines
  - duplicate item detection on PO lines (client-side note only)
  - GRN line dropdown driven from selected PO line JSON payload
  - pending qty shown on screen
  - GRN qty `max` attribute set from pending qty

## Validation currently present
### PO
- Server-side validation exists for required vendor/date/lines/item/qty.
- Unit price currently allows zero because rule is `nullable|numeric|min:0`.
- Hidden/stale item mismatch is partially protected because server requires `lines.*.item_id` to exist.
- No explicit server check that unit price must be `> 0`.

### GRN
- Server-side validation exists for PO, PO line, item, received date, qty.
- `unit_cost` currently has no validation and may be zero.
- Over-receipt is blocked server-side.
- Zero pending is blocked server-side.
- Item/PO-line mismatch is blocked server-side.
- Current received/pending math is inconsistent for multi-line POs because received qty is summed at PO level in some places.

## Status truth
- PO statuses observed in code:
  - `DRAFT` (migration default)
  - `ISSUED` (PO create)
  - `APPROVED` (PO approval)
  - `PARTIALLY_RECEIVED` (GRN create)
  - `RECEIVED` (GRN create and GRN approval)
- GRN has no dedicated status column.
- GRN list currently displays static text: `Posted on Create`.

## Stock posting truth
- Stock posting happens in `ProcurementController::storeGrn()` only, via `StockTransaction::create([... 'txn_type' => 'GRN', 'reference_type' => GoodsReceipt::class, 'reference_id' => $grn->id ...])`
- `approveGrn()` does not create stock transactions.
- Current architecture claims stock posts on create and not on approval.

## Approval / acknowledgement truth
- PO approval is currently only status mutation.
- GRN acknowledgement is currently only PO status mutation + GRN touch.
- No deeper workflow engine currently exists in procurement.
