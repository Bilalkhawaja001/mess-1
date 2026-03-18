# PAYMENT_SCHEMA_REPORT

## New / Updated Tables
1. `payment_methods`
2. `payment_attempts`
3. `payment_transactions`
4. `payment_reconciliations`
5. Updated `payments` with architecture columns:
   - `bill_id`, `payment_method_id`, `payment_ref`, `currency`
   - `refunded_amount`, `reversed_amount`
   - `last_attempt_id`, `last_transaction_id`

## Key Data Fields
- Attempts: `attempt_ref`, amount, status, audit payload
- Transactions: internal/external/merchant refs, idempotency key, raw req/resp summary, initiated/completed/verified timestamps
- Reconciliation: ledger/accounting sync statuses + reconciled metadata

## Relationships
- member -> bills/payments/payment_attempts/payment_transactions
- bill -> paymentAttempts/paymentTransactions/payments
- payment -> method/attempts/transactions/reconciliations
