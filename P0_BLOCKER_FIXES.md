# P0_BLOCKER_FIXES

Date: 2026-03-18
Scope: **P0 only**

## Minimal fixes applied

1. **Auth redirect fix (P0 blocker)**
   - File: `routes/web.php`, `AuthController.php`
   - Change: renamed login GET route to `login` and updated redirects to use `route('login')`.
   - Outcome: unauthenticated protected-route access now redirects correctly instead of 500.

2. **Wired missing P0 routes**
   - File: `routes/web.php`
   - Added routes for:
     - auth reset request/consume + password change
     - month governance (index/close/reopen/hard-reset)
     - billing correction
     - payment edit
     - ledger import/recompute

3. **Enabled controller actions for P0 workflow**
   - File: `app/Http/Controllers/AuthController.php`
     - added `requestPasswordReset`, `consumePasswordReset`, `changePassword`.
   - File: `app/Http/Controllers/Admin/BillingController.php`
     - added `correct` action using `BillingCorrectionService`.
   - File: `app/Http/Controllers/Admin/PaymentController.php`
     - added `edit` action using `PaymentEditService`.

4. **Billing correction SQL constraint fix**
   - File: `app/Services/Billing/BillingCorrectionService.php`
   - Change: switched correction from “create replacement duplicate row” to in-place correction update (preserves unique constraint on `month_cycle,member_id`).

5. **Model mass-assignment alignment for P0 columns**
   - Files:
     - `app/Models/Billing.php`
     - `app/Models/Payment.php`
     - `app/Models/MemberLedger.php`
   - Added new P0 columns to `fillable` arrays.

6. **XLSX export end-to-end fix**
   - Dependency: added `phpoffice/phpspreadsheet`.
   - File: `app/Http/Controllers/Admin/SummaryController.php`
     - Added XLSX generation (`export=xlsx`) using `Spreadsheet` + `Xlsx` writer.
   - File: `app/Http/Requests/Summary/SummaryFilterRequest.php`
     - Allowed `export` values: `csv,xlsx`.

7. **Audit logging coverage for exports**
   - File: `app/Http/Controllers/Admin/SummaryController.php`
   - Added `summary.export.csv` and `summary.export.xlsx` audit events.

## Why these are minimal

- No UI/theme redesign.
- No non-P0 feature addition.
- Only route wiring + controller/service/validation/model updates required to unblock P0 workflow and audit/export verification.
