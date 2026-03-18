# UAT Checklist - Member Registration & Super Admin Account Controls

## Self Registration
1. Valid member + mobile + otp + email/password: **Implemented, pending runtime execution**
2. Invalid member id: **Handled (generic error), pending runtime execution**
3. Mobile mismatch: **Handled (generic error), pending runtime execution**
4. Already registered member: **Handled, pending runtime execution**
5. Expired OTP: **Handled, pending runtime execution**
6. Wrong OTP multiple attempts: **Handled with lock, pending runtime execution**
7. Resend OTP: **Handled with cooldown + cap, pending runtime execution**
8. Duplicate email: **Handled by validation, pending runtime execution**
9. Successful login after registration: **Supported (username=email), pending runtime execution**

## Super Admin
1. Create account for unregistered member: **Implemented, pending runtime execution**
2. Prevent duplicate member account: **Implemented, pending runtime execution**
3. Deactivate/reactivate portal access: **Implemented, pending runtime execution**
4. Reset member access: **Implemented, pending runtime execution**
5. Force password change on first login: **Implemented, pending runtime execution**
6. Audit entries for all actions: **Implemented, pending runtime execution**

## Verification Note
- Automated tests/migrations could not be executed in this environment because PHP CLI is unavailable (`php` command not found).
