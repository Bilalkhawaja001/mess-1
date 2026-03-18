# Routes Added Report

## Public (guest)
- `POST /password-reset/request` (`password-reset.request.public`)
- `POST /password-reset/consume` (`password-reset.consume.public`)
- `GET /register/member` (`member.register.start`)
- `POST /register/member` (`member.register.start.submit`) [throttle]
- `GET /register/member/verify` (`member.register.verify`)
- `POST /register/member/verify` (`member.register.verify.submit`) [throttle]
- `POST /register/member/resend` (`member.register.resend`) [throttle]
- `GET /register/member/complete` (`member.register.complete`)
- `POST /register/member/complete` (`member.register.complete.submit`)

## Admin
- `GET /admin/member-accounts` (`admin.member-accounts.index`)
- `POST /admin/member-accounts` (`admin.member-accounts.store`)
- `POST /admin/member-accounts/{member}/activate` (`admin.member-accounts.activate`)
- `POST /admin/member-accounts/{member}/deactivate` (`admin.member-accounts.deactivate`)
- `POST /admin/member-accounts/{member}/reset` (`admin.member-accounts.reset`)
- `POST /admin/member-accounts/{member}/unlock-otp` (`admin.member-accounts.unlock-otp`)
- `POST /admin/member-accounts/{member}/mark-mobile-verified` (`admin.member-accounts.mark-mobile-verified`)
