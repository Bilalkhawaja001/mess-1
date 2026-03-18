# Module UAT Results (Runtime Verification)

Source of truth: `runtime_verification_evidence.json`

## 1) Inventory — **PASS**
- Endpoints discovered in `route:list`:
  - `GET /admin/inventory`
  - `POST /admin/inventory/items`
  - `POST /admin/inventory/transactions`
- Smoke result: `GET /admin/inventory` → **302** (expected auth redirect)
- CRUD evidence:
  - Item created: `sku=SKU-rv...`, `item_id=1`
  - Opening stock transaction created (`txn_type=OPENING`)
- Business logic:
  - `InventoryService::balanceForItem` expected `135` = opening `100` + GRN `40` - kitchen issue `5`
  - Actual `135` → pass

## 2) Procurement (Vendors / PO / GRN) — **PASS**
- Endpoints:
  - `GET /admin/procurement`
  - `POST /admin/procurement/vendors`
  - `POST /admin/procurement/po`
  - `POST /admin/procurement/grn`
- Smoke result: `GET /admin/procurement` → **302**
- CRUD evidence:
  - Vendor created (`vendor_id=1`)
  - PO created (`po_id=1`, initial status `ISSUED`)
  - GRN posted (`grn_id=1`)
  - PO status auto-updated to `RECEIVED`
  - StockTransaction with `txn_type=GRN` posted

## 3) Meal planning / Kitchen — **PASS**
- Endpoints:
  - `GET /admin/kitchen`
  - `POST /admin/kitchen/menus`
  - `POST /admin/kitchen/recipes`
  - `POST /admin/kitchen/plans`
  - `POST /admin/kitchen/issues`
- Smoke result: `GET /admin/kitchen` → **302**
- CRUD evidence:
  - Menu created (`menu_id=1`)
  - Recipe created (`recipe_id=1`)
  - Meal plan created (`plan_id=1`)
  - Kitchen issue created (`issue_id=1`)
  - Linked stock transaction posted (`txn_type=KITCHEN_ISSUE`, `issue_txn_id=3`)

## 4) Guest management — **PASS**
- Endpoints:
  - `GET /admin/guests`
  - `POST /admin/guests`
  - `POST /admin/guests/meals`
- Smoke result: `GET /admin/guests` → **302**
- CRUD evidence:
  - Guest created (`guest_id=1`)
  - Guest meal created (`guest_meal_id=1`)
- Business logic evidence:
  - Amount formula verified: `quantity 2 * rate 350 = amount 700`

## 5) Department / Mess accounting — **PASS**
- Endpoints:
  - `GET /admin/accounting`
  - `POST /admin/accounting/departments`
  - `POST /admin/accounting/messes`
  - `POST /admin/accounting/entries`
- Smoke result: `GET /admin/accounting` → **302**
- CRUD evidence:
  - Department created (`department_id=1`)
  - Mess created (`mess_id=1`)
  - Ledger entry posted (`entry_id=1`, DEBIT 1200)
- Business logic evidence:
  - Net department cost aggregation verified: actual `1200`

## 6) Downloads / Export center — **PASS**
- Endpoints:
  - `GET /admin/exports`
  - `GET /admin/exports/stock-ledger`
  - `GET /admin/exports/guest-meals`
  - `GET /admin/exports/department-ledger`
- Smoke results: all exports routes return **302** when unauthenticated (expected)
- CSV verification (runtime controller response):
  - Stock ledger export: status `200`, filename `stock-ledger.csv`, content-type `text/csv`
  - Guest meals export: status `200`, filename `guest-meals.csv`, content-type `text/csv`
  - Department ledger export: status `200`, filename `department-ledger.csv`, content-type `text/csv`

---

## Explicit FAIL marking rule check
No scoped module was found in code but failing at runtime in this verification pass.  
**FAIL count: 0**
