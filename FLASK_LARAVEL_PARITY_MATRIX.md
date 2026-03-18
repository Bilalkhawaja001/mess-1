# FLASK_LARAVEL_PARITY_MATRIX

Date: 2026-03-18  
Canonical (read-only): `mess_billing_mvp_phase6_ui_workflow` (Flask)  
Audit target: `mess_billing_laravel_app` (Laravel)

## Classification legend
- MATCHED
- PARTIAL
- MISSING
- BEHAVIOR_MISMATCH
- NOT_IN_SCOPE

## Launch-critical parity matrix

| # | Canonical item (Flask) | Laravel status | Class | Evidence snippet |
|---|---|---|---|---|
| 1 | `/login` GET/POST + login workflow | Implemented | MATCHED | Flask route inventory has `/login` GET/POST; Laravel has `Route::get('/login')` + `Route::post('/login')` (`routes/web.php:23-24`). Reverification: `POST /login -> 302 Found` (`P0_FINAL_REVERIFICATION.md`). |
| 2 | `/logout` POST | Implemented | MATCHED | Flask `/logout` exists; Laravel `Route::post('/logout')` (`routes/web.php:26`). |
| 3 | `/change-password` GET/POST page workflow | Only admin POST endpoint, no canonical GET route | BEHAVIOR_MISMATCH | Flask route is `/change-password` GET/POST; Laravel only `Route::post('/auth/password-change')` (`routes/web.php:39`). |
| 4 | `/password-reset/request` GET/POST | Only admin POST endpoint | BEHAVIOR_MISMATCH | Flask has `/password-reset/request` GET/POST; Laravel has `Route::post('/auth/password-reset/request')` (`routes/web.php:37`). |
| 5 | `/password-reset` GET/POST consume token | Only admin POST consume endpoint | BEHAVIOR_MISMATCH | Flask has `/password-reset` GET/POST; Laravel has `Route::post('/auth/password-reset/consume')` (`routes/web.php:38`). |
| 6 | Admin dashboard (`/dashboard` Flask) | Implemented under `/admin/dashboard` | MATCHED | Laravel route exists in admin group (`routes/web.php:35`). |
| 7 | Users management (`/users` GET/POST + update/toggle) | Implemented | MATCHED | Laravel users routes (`routes/web.php:41-44`). |
| 8 | Members create/update/deactivate/reactivate | Implemented via toggle active | PARTIAL | Laravel member CRUD/toggle exists (`routes/web.php:46-49`), but canonical has dedicated deactivate/reactivate/remove endpoints (Flask route inventory). |
| 9 | Members bulk upload/sample | Not implemented | MISSING | Flask has `/members/bulk-upload` and `/members/bulk-sample`; no corresponding Laravel route in `routes/web.php`. |
| 10 | Daily attendance GET/POST | Implemented | MATCHED | Laravel attendance routes (`routes/web.php:51-52`). |
| 11 | Monthly attendance GET/POST + approve/unlock | Base implemented; approve/unlock missing | PARTIAL | Laravel has `/attendance-monthly` GET/POST (`routes/web.php:53-54`), but no `/attendance-monthly/approve` or `/attendance-monthly/unlock`. |
| 12 | Extras GET/POST | Implemented | MATCHED | Laravel extras routes (`routes/web.php:56-57`). |
| 13 | Rates GET/POST + approve/lock/update/delete | Partial (approve + active toggle only) | PARTIAL | Laravel has `toggle-approve`, `toggle-active` (`routes/web.php:61-62`), missing canonical lock/update/delete endpoints. |
| 14 | Billing page + generate | Implemented | MATCHED | Laravel `/billing` + `/billing/generate` (`routes/web.php:64-65`). |
| 15 | Billing correction (`/billing/<id>/correct`) | Implemented + executed | MATCHED | Laravel `Route::post('/billing/{billing}/correct')` (`routes/web.php:66`); reverification `POST .../correct -> 302` with audit action `billing.corrected`. |
| 16 | Payments GET/POST + approve | Implemented | MATCHED | Laravel `/payments`, `/payments/{payment}/approve` (`routes/web.php:68-71`). |
| 17 | Payment edit (`/payments/<id>/edit`) | Implemented + executed | MATCHED | Laravel `Route::post('/payments/{payment}/edit')` (`routes/web.php:70`); reverification `POST .../edit -> 302`, audit action `payment.edited`. |
| 18 | Ledger view + manual adjustments | Implemented | MATCHED | Laravel `/ledger` + `/ledger/adjustments` (`routes/web.php:79-80`). |
| 19 | Ledger import/opening/recompute tooling | Implemented with route-contract differences | BEHAVIOR_MISMATCH | Canonical endpoints: `/admin/import-ledger`, `/admin/import-opening-balances`, `/admin/recompute-ledger`; Laravel uses `/ledger/import` and `/ledger/recompute` (`routes/web.php:80-81`). Workflow executed in reverification. |
| 20 | Month close/reopen/hard reset | Implemented with different endpoint contract | BEHAVIOR_MISMATCH | Flask: `/month-close`, `/month-reopen`, `/month-reset-hard`; Laravel uses `/month-governance/close|reopen|hard-reset` (`routes/web.php:74-76`). Reverification confirms execution + audit logs. |
| 21 | Summary report | Implemented | MATCHED | Laravel `/summary` (`routes/web.php:83`) with CSV/XLSX export behavior verified (200 OK). |
| 22 | Reports dashboard | Implemented | MATCHED | Laravel `/reports` (`routes/web.php:84`). |
| 23 | Statement report | Implemented | MATCHED | Laravel `/statement` (`routes/web.php:85`). |
| 24 | Bills download/export endpoints | Not canonical-equivalent endpoint set | PARTIAL | Flask has `/reports/bills-download/export.csv|xlsx`; Laravel exports exist on `/summary?export=csv|xlsx` (SummaryController) but no `/reports/bills-download` route contract. |
| 25 | Audit log UI route `/audit-log` | Controller/view exist but route not wired | MISSING | `app/Http/Controllers/Admin/AuditLogController.php` + `resources/views/admin/audit/index.blade.php` exist; no `/audit-log` route entry in `routes/web.php`. |
| 26 | Settings `/settings` + app controls | Implemented (core) | PARTIAL | Laravel settings routes exist (`routes/web.php:87-89`), canonical also has `/admin/settings/app` behavior set not exposed as separate contract. |
| 27 | Role/permission enforcement | Implemented (RBAC middleware + permission schema) | MATCHED | Role middleware enforces 403 (`app/Http/Middleware/RoleMiddleware.php`); reverification shows admin/member 200/403 boundaries; permissions/role_permissions tables created in migration `2026_03_18_102600...`. |
| 28 | Audit trail persistence for P0 actions | Implemented | MATCHED | `audit_logs` migration present; reverification: `audit_logs_count=11` with actions month/billing/payment/ledger/password/export. |

## Full-parity (non-launch) matrix

| # | Canonical module (Flask) | Laravel status | Class | Evidence |
|---|---|---|---|---|
| 29 | Inventory (`/items`, `/stock-*`) | Not implemented | MISSING | Present in Flask route inventory; absent from Laravel routes/controllers. |
| 30 | Procurement (`/vendors`, `/po`, `/grn`) | Not implemented | MISSING | Present in Flask; absent in Laravel. |
| 31 | Meals (`/menus`, `/recipes`, `/meal-plans`, `/kitchen-issue`) | Not implemented | MISSING | Present in Flask; absent in Laravel. |
| 32 | Guest management (`/guests`, `/guest-meals`) | Not implemented | MISSING | Present in Flask; absent in Laravel. |
| 33 | Department/mess accounting (`/departments`, `/messes`, `/department-ledger`, `/finance-reports`) | Not implemented | MISSING | Present in Flask; absent in Laravel. |
| 34 | Member portal details (`/member/bill`, `/member/attendance`, `/member/payments`, `/member/profile`) | Not implemented (dashboard only) | MISSING | Laravel only member route is `/member/dashboard` (`routes/web.php:92-94`). |
| 35 | P2 ERP scope impact on launch decision | Deferred by launch scope | NOT_IN_SCOPE | Explicitly listed in `LAUNCH_CRITICAL_SCOPE.json` under out-of-launch scope. |

## Count summary (all audited items above)
- MATCHED: 16
- PARTIAL: 5
- MISSING: 8
- BEHAVIOR_MISMATCH: 5
- NOT_IN_SCOPE: 1
- Total audited items: 35
