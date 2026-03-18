# PAYMENT_ROUTES_AND_PERMISSIONS

## Admin Routes
- `GET /admin/payments` -> `payments.view_admin`
- `POST /admin/payments` -> `payments.manual_record_admin`
- `POST /admin/payments/{payment}/edit` -> `payments.override_status_admin`
- `POST /admin/payments/{payment}/approve` -> `payments.verify_admin`
- `POST /admin/payments/transactions/{transaction}/verify` -> `payments.verify_admin`
- `POST /admin/payments/reconciliations/{reconciliation}/reconcile` -> `payments.reconcile_admin`

## Member Routes
- `GET /member/payments` -> `payments.view_own`
- `POST /member/payments/initiate` -> `payments.initiate_own`

## Added Permission Codes
- `payments.view_own`
- `payments.initiate_own`
- `payments.view_admin`
- `payments.verify_admin`
- `payments.reconcile_admin`
- `payments.manual_record_admin`
- `payments.refund_admin`
- `payments.override_status_admin`
