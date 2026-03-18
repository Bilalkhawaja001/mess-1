# Phase-4 Mapping + Implementation (Summary, Reports, Statement, Rates, Extras, Billing Idempotency)

## Findings (Flask parity)
- `/extras`: add extra by member/date/desc/amount + list recent.
- `/rates`: add policy with overlap validation, approve/unapprove, active toggle.
- `/summary`: month filter + export (Flask excel export).
- `/reports`: month recovery and member ledger snapshots.
- `/statement`: filter by member/month/range + running balance + print-friendly output.
- Billing gap from Phase-3: Flask `billing_runs` + `scope_hash/config_hash` idempotency.

## Laravel implementation (Phase-4)
### New models
- `RatePolicy`, `Extra`, `BillingRun`

### New migrations
- `create_rate_policies_table`
- `create_extras_table`
- `create_billing_runs_table`

### New controllers
- `Admin\RateController`
- `Admin\ExtraController`
- `Admin\SummaryController`
- `Admin\ReportController`
- `Admin\StatementController`

### New validation requests
- `StoreRatePolicyRequest`
- `StoreExtraRequest`
- `SummaryFilterRequest`

### Updated service
- `BillingGenerationService` now supports:
  - `config_hash` from active rates payload
  - `scope_hash` from month+members+config
  - run guard table `billing_runs`
  - return `already_generated` when matching scope already processed
  - extras integration in billing net amount
  - active PER_DAY rate selection (fallback to form/default)

### New views
- `admin/extras/index.blade.php`
- `admin/rates/index.blade.php`
- `admin/summary/index.blade.php`
- `admin/reports/index.blade.php`
- `admin/statement/index.blade.php`

### Routes added
- `/admin/extras` GET/POST
- `/admin/rates` GET/POST
- `/admin/rates/{rate}/toggle-approve` POST
- `/admin/rates/{rate}/toggle-active` POST
- `/admin/summary` GET (+ `export=csv`)
- `/admin/reports` GET
- `/admin/statement` GET

## Parity notes / known gaps
1. **Summary export**
   - Flask: excel export helper.
   - Laravel: CSV export implemented (shared-hosting safe, no package dependency).
2. **Rates enum strictness**
   - Flask has explicit `RATE_TYPES` constant.
   - Laravel currently open string input (still validates non-negative and overlap); strict enum list should be finalized from production constants in later hardening.
3. **Statement debug counters**
   - Flask has richer debug payload/count tracking.
   - Laravel currently focuses on print ledger table + running balance parity.
4. **Month closure gate**
   - core structure exists (`billing_cycles`) but strict global block for all posting endpoints should be centralized in Phase-5 middleware/service rule.

## Risky assumptions documented
- PER_DAY rate used for billing computation where active approved rate exists; else fallback rate used.
- extras are included as monthly sum in billing generation.
- report recovery currently uses approved payments sum and adjustment placeholder=0 until separate adjustment categorization extension.
