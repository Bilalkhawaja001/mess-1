# Member OTP Flow Report

## OTP Storage & Lifecycle
- Table: `member_registration_otps`
- Fields include: `otp_hash`, `expires_at`, `attempts`, `resend_count`, `last_sent_at`, `status`, audit context fields.
- OTP never stored in plaintext; hashed with Laravel Hash.

## Rules Implemented
- Length: 6 digits
- Expiry: 5 minutes (`member_registration.otp.ttl_seconds=300`)
- Resend cooldown: 60 seconds
- Max verify attempts: 5
- Max resend attempts: 5
- Invalidate active OTP before new OTP issue.

## Audit Events
- `member.registration.otp.sent`
- `member.registration.otp.resent`
- `member.registration.otp.verify.success`
- `member.registration.otp.verify.failed_wrong`
- `member.registration.otp.verify.failed_expired`
- `member.registration.otp.verify.failed_locked`
- `member.registration.otp.resend.failed_limit`

## Delivery Abstraction
- Interface: `App\Services\Otp\OtpDeliveryService`
- Drivers:
  - `log` -> `LogOtpDeliveryService`
  - `fake` -> `FakeOtpDeliveryService`
- Config key: `MEMBER_OTP_DRIVER`
