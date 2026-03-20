# Flask → Laravel Parity Matrix (Pass 2)

Generated: 2026-03-20
Scope: Flask `route_inventory.csv` + operational handlers in `app.py` vs Laravel `routes/web.php`, controllers, and views.

## High-value admin workflow parity status

| Flask route/workflow | Laravel status before | Action in this pass | Final |
|---|---|---|---|
| `/audit-log` | Controller/view existed but no active route in nav flow | Added routed endpoint (`/admin/audit-log` + legacy `/audit-log`) and sidebar link | ✅ |
| `/api/menus` | Missing JSON endpoint | Added authenticated JSON endpoint via `KitchenController@apiMenus` | ✅ |
| `/api/guest-rate` | Missing JSON endpoint | Added authenticated JSON endpoint via `GuestController@guestRate` | ✅ |
| `/health`, `/ready` | Missing lightweight probes | Added probe routes returning JSON | ✅ |
| `/menus/* (edit/delete)` | Create only, no edit/delete action routes | Added menu update/delete endpoints in kitchen module | ✅ |
| `/recipes/* (edit/delete)` | Create only, no edit/delete action routes | Added recipe update/delete endpoints | ✅ |
| `/meal-plans/* (edit/approve)` | Create only | Added meal-plan edit/approve endpoints | ✅ |
| `/kitchen-issue/* (approve)` | Create only | Added kitchen-issue approve endpoint | ✅ |
| Kitchen UI pages (menus/recipes/plans/issues) | Placeholder page | Replaced with operational forms + action tables | ✅ |
| Procurement page (`/po`, `/grn`, approve actions) | Placeholder page despite backend methods existing | Added operational UI for vendor/PO/GRN create + approve flows | ✅ |

## Intentionally out-of-scope / remaining

1. Flask-only debug/dev endpoints (e.g., `/debug/*`, `/dev-login`, `/whoami`, `/csrf-token`) intentionally not ported as production parity targets.
2. Stock count/stock transaction approval-specific flows from Flask were not implemented as dedicated governance objects because current Laravel schema does not yet include stock-count domain tables/approval metadata. Existing inventory transactions remain operational.
3. Member portal extended pages (`/member/bill`, `/member/attendance`, `/member/profile`) remain partial relative to Flask’s broader member view set.

## Notes

- No schema migration added in this pass.
- Changes focus on operational parity for admin create/update/delete/approve and API assist endpoints.
