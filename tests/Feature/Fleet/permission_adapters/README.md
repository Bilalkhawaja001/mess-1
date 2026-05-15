# Fleet Permission Test Adapters

Adapters are loaded in this exact priority order:

1. `SpatiePermissionAdapter` for `spatie/laravel-permission`.
2. `NativeRolePermissionAdapter` for apps where the User model exposes `permissions()`.
3. `CustomTablePermissionAdapter` for `permissions` + `permission_user` pivot tables.
4. `GatePolicyAdapter` only when explicitly enabled with `config(['fleet_test_permissions.enable_gate_policy_adapter' => true])`.

Spatie middleware apps (`permission:*`) must use the Spatie adapter. The Gate fallback is valid only for Gate/Policy-based authorization and must not be used to make `permission:*` middleware tests pass falsely.

For a custom permission system, add a class implementing `FleetPermissionAdapterInterface` and place it before `GatePolicyAdapter` in `fleetPermissionAdapters()`.
