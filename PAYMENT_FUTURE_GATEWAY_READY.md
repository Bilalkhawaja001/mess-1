# PAYMENT_FUTURE_GATEWAY_READY

## Ready Hooks
- Redirect-based flow: `PaymentAttemptService` + transaction refs
- QR/manual reference flow: `reference_no` + `merchant_ref`
- Webhook/callback async: idempotency key + transition service
- App handoff flow: gateway adapter abstraction supports payload contracts
- Retry flows: failed/expired -> initiated transitions allowed (controlled)

## Integration Steps (Future)
1. Implement real gateway adapter(s) against `PaymentGatewayInterface`.
2. Add callback endpoint + signature verification.
3. Map provider statuses to domain statuses via `PaymentService::transition`.
4. Extend reconciliation sync with accounting/ledger posting jobs.

## Constraint Reminder
Current implementation is architecture-ready only, **NOT live payment-ready**.
