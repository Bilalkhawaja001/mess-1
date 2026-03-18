# Super Admin Provisioning Report

Generated at: 2026-03-18 16:08 PKT

## Scope Implemented
- Added dedicated `SuperAdminSeeder` and wired it in `DatabaseSeeder`.
- Provisioning is idempotent:
  - If no SUPER_ADMIN exists, creates one default SUPER_ADMIN.
  - If SUPER_ADMIN already exists, does not create duplicate.
- Supports env overrides:
  - `SUPERADMIN_NAME`
  - `SUPERADMIN_EMAIL`
  - `SUPERADMIN_USERNAME`
- Always enforces `must_change_password = true` for the active provisioned SUPER_ADMIN.
- Generates strong temporary password at seed runtime (never hardcoded in source).
- Writes runtime provisioning metadata to `storage/app/superadmin_provisioning_runtime.json`.

## Current Provisioning Outcome
- Status: `already_exists_password_rotated`
- Active provisioned SUPER_ADMIN user_id: `3`
- Existing account reused: **Yes**
- Duplicate account created: **No**

## Runtime Credentials (deployment artifact only)
- Email: `sa_scope20260318092942@example.com`
- Username: `sa_scope20260318092942`
- Temporary Password: `E_ct;EC#d:VZ?k\N86RDC,}T`

## Duplicate Super Admin Remediation
- Extra SUPER_ADMIN accounts found and demoted to ADMIN to enforce exactly one SUPER_ADMIN.
- Demoted user IDs: `5`

## Security Notes
- Temporary password is generated per seed run and stored only in runtime artifact/output.
- Rotate/remove this credential after first login password change.
- Do not expose these credentials in UI or public logs.
