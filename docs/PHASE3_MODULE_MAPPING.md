# Phase-3 Deep Mapping (Billing, Payments, Ledger)

## Findings (Flask parity audit)

### Billing (Flask)
- Route: `/billing` (GET/POST)
- Generate workflow by `month_cycle`.
- Business rules:
  - month must be open
  - member eligibility by join/leave date window
  - attendance-driven day count fallback
  - no duplicate bill per month/member
  - creates bill rows + ledger debit posting
  - run idempotency via scope/config hash (`billing_runs`)

### Payments (Flask)
- Route: `/payments` (GET/POST) draft posting.
- Route: `/payments/<id>/approve` approval/posting.
- Business rules:
  - payment method in strict enum
  - amount > 0
  - month-open check on payment date
  - draft -> approved transition only
  - approval posts credit ledger entry

### Ledger (Flask)
- Route: `/ledger` (GET/POST)
- GET filter by member and flags.
- POST manual adjustment (debit/credit)
- Running balance recomputed deterministically for selected member.

---

## Laravel implementation delivered

### Routes
- `/admin/billing` GET
- `/admin/billing/generate` POST
- `/admin/payments` GET/POST
- `/admin/payments/{payment}/approve` POST
- `/admin/ledger` GET
- `/admin/ledger/adjustments` POST

### Controllers
- `Admin\BillingController`
- `Admin\PaymentController`
- `Admin\LedgerController`

### Service
- `App\Services\Billing\BillingGenerationService`

### Validation
- `GenerateBillingRequest`
- `StorePaymentRequest`
- `StoreLedgerAdjustmentRequest`

### Models
- `Billing`
- `Payment`
- `MemberLedger`
- `Member` relations updated (`billings`, `payments`, `ledgers`)

### Migrations
- `create_billings_table`
- `create_payments_table`
- `create_member_ledgers_table`

### Views
- `admin/billing/index.blade.php`
- `admin/payments/index.blade.php`
- `admin/ledger/index.blade.php`

---

## Parity notes (important)

1. **Billing idempotency hashing (`billing_runs`)**
   - Flask has config/scope hashing guard.
   - Laravel Phase-3 currently uses unique `(month_cycle, member_id)` + skip count.
   - This is functional parity-lite, not full hash parity.

2. **Extras/rates complex logic**
   - Flask billing includes extras and dynamic rate policies.
   - Laravel Phase-3 currently uses provided `rate_per_day` (default 100) and extras=0 placeholder.
   - Full financial parity must be added in Phase-4/5.

3. **Month closure enforcement**
   - Flask enforces month open/closed centrally.
   - Laravel has `billing_cycles` base but strict month lock gate is not fully wired across modules yet.

4. **Journal voucher posting**
   - Flask supports COA/journal posting integration.
   - Laravel Phase-3 currently posts to member ledger only.

5. **Ledger running balance parity**
   - Implemented deterministic recompute on filtered member view similar to Flask behavior.

---

## Risky assumptions documented
- Rate per day fallback set to `100` unless provided in billing form.
- Adjustments posted with `ref_type=ADJUSTMENT` and `ref_id=0` (same conceptual behavior as Flask adjustment post).
- Payments approval creates ledger credit using last known balance snapshot.

