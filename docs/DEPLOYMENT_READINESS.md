# DEPLOYMENT_READINESS

## Shared-hosting target assumptions
- PHP 8.2+ enabled
- MySQL access available
- `public/` document root supported (or subfolder mapping by hosting panel)
- write access for: `storage/`, `bootstrap/cache/`

## Required deployment steps
1. Upload project files.
2. Create `.env` from `.env.example` and set real DB/app values.
3. Generate app key: `php artisan key:generate`.
4. Run migrations: `php artisan migrate --force`.
5. Seed roles: `php artisan db:seed --class=RoleSeeder`.
6. Configure web root to Laravel `public/`.
7. Ensure permissions for writable folders.
8. Set APP_DEBUG=false.

## Runtime checks
- Login works
- Admin dashboard loads
- Billing generation works
- Payment approval posts ledger
- Statement print view works

## Notes
- Current machine lacks php/composer in PATH, so commands above are deployment-environment execution tasks.
