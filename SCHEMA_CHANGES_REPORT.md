# Schema Changes Report

## New Migrations
1. `2026_03_18_141000_add_member_portal_registration_fields.php`
   - `members.mobile_number` (nullable)
   - `members.portal_enabled` (bool, default true)
   - `members.mobile_verified_at` (nullable timestamp)
   - `members.registered_at` (nullable timestamp)
   - `users.member_id` (nullable FK -> members, unique)

2. `2026_03_18_141100_create_member_registration_otps_table.php`
   - OTP table for registration security and lifecycle tracking.

## Existing Linkage
- Existing `members.user_id` unique remains in place.
- New `users.member_id` unique ensures one-member-one-user support from user side.
