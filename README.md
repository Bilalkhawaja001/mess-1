# Mess Billing Laravel Runtime

Laravel-based Mess Billing system (admin-first pilot) migrated from Flask reference.

## Purpose
- Manage users, members, attendance (daily/monthly), billing, payments, ledger, reports, statement, settings.
- GitHub stores **code only**.
- Live/production data must live in **hosting MySQL**, not in local machine or GitHub.

## Requirements
- PHP 8.2+ (8.3 recommended)
- Composer 2.x
- MySQL 8+

## Local setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan serve --host=127.0.0.1 --port=8000
```

## Clone from GitHub
```bash
git clone <repo-url>
cd mess_billing_laravel_runtime
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
```

## Production deploy (shared hosting/cPanel)
1. Create MySQL DB + user on hosting.
2. Upload/clone project to server.
3. Create server `.env` manually (never commit `.env`).
4. Set:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - hosting DB credentials
5. Run:
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
```
6. Ensure writable folders:
   - `storage/`
   - `bootstrap/cache/`
7. Point document root to Laravel `public/`.

## Security note
- Do not commit secrets (`.env`, keys, passwords, tokens).
- Repo is deployment source code only.
