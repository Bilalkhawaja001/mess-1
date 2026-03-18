# Functional Completeness Plan

Implemented module boundaries, data model, workflows and acceptance criteria for mandatory missing modules.

- Inventory: items + stock_transactions (ledger/balance/count support)
- Procurement: vendors, PO, GRN with stock impact
- Meal/Kitchen: menus, recipes, meal plans, kitchen issues with consumption footprint
- Guest: guests + guest meals with amount calculation
- Department/Mess accounting: departments, messes, department ledger + net costing
- Export center: downloadable CSV for core ledgers

Acceptance criteria: creation flows + postings + export outputs + no regression in existing P0 routes/services.
