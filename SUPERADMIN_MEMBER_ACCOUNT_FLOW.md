# Super Admin Member Account Flow

## Endpoints (Super Admin permission-guarded)
- `GET /admin/member-accounts` view page
- `POST /admin/member-accounts` create member account
- `POST /admin/member-accounts/{member}/activate`
- `POST /admin/member-accounts/{member}/deactivate`
- `POST /admin/member-accounts/{member}/reset`
- `POST /admin/member-accounts/{member}/unlock-otp`
- `POST /admin/member-accounts/{member}/mark-mobile-verified`

## Behaviors
- Prevent duplicate account by member linkage check.
- Enforce unique email via validation.
- Assign MEMBER role automatically.
- Optional flags:
  - force password change
  - mark mobile verified
- Reset action issues temporary password and forces change on next login.

## Audit Events
- `admin.member_account.created`
- `admin.member_account.activated`
- `admin.member_account.deactivated`
- `admin.member_account.reset`
- `admin.member_account.otp_unlock`
- `admin.member_account.mobile_verified_marked`
