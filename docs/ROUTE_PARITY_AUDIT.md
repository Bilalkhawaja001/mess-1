# ROUTE_PARITY_AUDIT

## Flask -> Laravel parity (active scope)

### Implemented parity routes
- `/users` -> `/admin/users` ✅
- `/members` -> `/admin/members` ✅
- `/attendance` -> `/admin/attendance` ✅
- `/attendance-monthly` -> `/admin/attendance-monthly` ✅
- `/extras` -> `/admin/extras` ✅
- `/rates` (+toggle approve/lock) -> `/admin/rates` (+toggle approve/active) ✅
- `/billing` -> `/admin/billing` + `/admin/billing/generate` ✅
- `/payments` + `/payments/{id}/approve` -> `/admin/payments` + `/admin/payments/{id}/approve` ✅
- `/ledger` -> `/admin/ledger` + `/admin/ledger/adjustments` ✅
- `/summary` -> `/admin/summary` ✅
- `/reports` -> `/admin/reports` ✅
- `/statement` -> `/admin/statement` ✅
- `/settings` -> `/admin/settings` ✅

### Member routes
- Flask member dashboard/bill/attendance/payments/profile
- Laravel currently has `/member/dashboard` only ⚠️ pending member module parity extension.

### Auth routes
- login/logout implemented ✅
- password reset/change password parity not fully migrated ⚠️

## Missing or partial parity
1. Member portal detail pages (bill/attendance/payments/profile) not yet implemented.
2. Password reset workflow not yet implemented.
3. Some advanced finance/admin submodules from Flask (department ledger/journal/inventory hubs) intentionally deferred.
