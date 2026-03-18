# OUTPUT_DIFFERENCE_REPORT

Date: 2026-03-18

## Scope
Output-level parity checks between Flask canonical behavior and Laravel post-implementation behavior for launch-critical visible outputs (redirect/status/export payloads/report output contracts).

## Output parity findings

| Output surface | Flask canonical expectation | Laravel observed output | Class | Evidence |
|---|---|---|---|---|
| Login submit | Successful auth redirects to role home | `POST /login -> 302 Found` and role split implemented in controller | MATCHED | `AuthController@login` role redirect + reverification status evidence. |
| Password reset request UX | Public `/password-reset/request` GET+POST page flow | Admin-scoped POST endpoint only (`/admin/auth/password-reset/request`) | BEHAVIOR_MISMATCH | `routes/web.php:37`; Flask route inventory has `/password-reset/request` GET,POST. |
| Password reset consume UX | Public `/password-reset` GET+POST with token consume | Admin-scoped POST `/admin/auth/password-reset/consume` | BEHAVIOR_MISMATCH | `routes/web.php:38`; Flask route inventory `/password-reset` GET,POST. |
| Change password UX | `/change-password` GET+POST route/page | Admin POST `/admin/auth/password-change` only | BEHAVIOR_MISMATCH | `routes/web.php:39`; Flask route inventory `/change-password` GET,POST. |
| Billing correction effect | Correction should execute and leave trace | `POST /admin/billing/{id}/correct -> 302`, audit action `billing.corrected` | MATCHED | `P0_FINAL_REVERIFICATION.md` audit action list. |
| Payment edit effect | Edit should execute and be auditable | `POST /admin/payments/{id}/edit -> 302`, audit action `payment.edited` | MATCHED | Reverification evidence. |
| Month governance effect | Close/reopen/hard reset actions with state transitions | All 3 endpoints execute (`302`) and write audit entries | PARTIAL | Behavior present but endpoint contract differs (`/month-governance/*` vs Flask `/month-*`). |
| Summary CSV export output | CSV download with report rows and totals | `200 OK`, CSV generated, header present, totals row present | MATCHED | Reverification: `storage/app/p0_summary.csv`, header proof. |
| Summary XLSX export output | XLSX download with equivalent columns/values | `200 OK`, XLSX generated, parsed; `A1=Member Code`, `C2=1999` | MATCHED | Reverification XLSX proof. |
| Bills-download endpoint contract | `/reports/bills-download` + csv/xlsx route family | Not present; export moved to summary query parameter flow | PARTIAL | Flask route inventory has explicit family; Laravel routes do not include that path. |
| Audit log page output | `/audit-log` page should render data | Controller/view exists but route missing => page unreachable by canonical route | MISSING | `AuditLogController` exists; no route entry in `routes/web.php`. |

## Top output/behavior mismatches
1. Auth reset/change flows are not exposed on canonical public route/page contracts.
2. Month governance and ledger toolchain are functionally present but endpoint contracts differ from Flask.
3. Bill export contract path family differs (`/summary?export=*` instead of `/reports/bills-download/*`).

## Quantified output classifications
- MATCHED: 5
- PARTIAL: 2
- MISSING: 1
- BEHAVIOR_MISMATCH: 3
- NOT_IN_SCOPE: 0
