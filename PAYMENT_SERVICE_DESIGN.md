# PAYMENT_SERVICE_DESIGN

## Services
- `PaymentService`: root payment creation + controlled status transitions
- `PaymentAttemptService`: attempt creation flow
- `PaymentTransactionService`: transaction recording, idempotency handling, manual verify
- `PaymentReconciliationService`: pending + final reconciliation updates
- `PaymentStatusTransitionService`: lifecycle transition gatekeeper

## Gateway Layer
- `PaymentGatewayInterface`
- `InternalFakeGatewayAdapter` (default)
- `JazzCashPlaceholderGatewayAdapter`
- `EasyPaisaPlaceholderGatewayAdapter`

All gateway adapters are internal placeholders. No merchant credentials used.
