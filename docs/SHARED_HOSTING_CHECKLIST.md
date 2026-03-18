# SHARED_HOSTING_CHECKLIST

## Pre-launch checklist
- [ ] PHP version >= 8.2
- [ ] Extensions enabled: pdo_mysql, mbstring, openssl, tokenizer, ctype, json, fileinfo
- [ ] MySQL DB created
- [ ] `.env` configured (APP_KEY, DB creds, APP_URL)
- [ ] `public/` set as web root
- [ ] `storage/` writable
- [ ] `bootstrap/cache/` writable
- [ ] Migrations executed
- [ ] Role seeder executed
- [ ] APP_DEBUG=false
- [ ] HTTPS forced at host/panel level

## Post-launch smoke
- [ ] `/login`
- [ ] `/admin/dashboard`
- [ ] `/admin/attendance-monthly`
- [ ] `/admin/billing`
- [ ] `/admin/payments`
- [ ] `/admin/ledger`
- [ ] `/admin/summary`
- [ ] `/admin/reports`
- [ ] `/admin/statement` + print
- [ ] `/admin/settings`
