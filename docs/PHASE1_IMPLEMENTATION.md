# Phase-1 Implementation Log

## Auth decision
- **Chosen:** custom lightweight auth scaffold (no Breeze/Jetstream dependency).
- Reason: php/composer currently unavailable on host; custom code-first scaffold keeps progress moving and shared-hosting friendly.

## Role model
- `roles` table + `users.role_id` FK
- Canonical role codes:
  - `SUPER_ADMIN`
  - `ADMIN`
  - `DATA_ENTRY`
  - `AUDITOR`
  - `MEMBER`

## Middleware structure
- `EnsureUserIsActive` -> blocks disabled accounts
- `RoleMiddleware` -> role-based route protection
- aliases in `app/Http/Kernel.php`:
  - `active`
  - `role`

## Flask shell -> Blade mapping
- Flask `base.html` => `resources/views/layouts/app.blade.php`
- Flask shared nav/top => `resources/views/partials/sidebar.blade.php`, `topbar.blade.php`
- Flask flashes => `resources/views/partials/flash.blade.php`
- Admin dashboard shell => `resources/views/admin/dashboard.blade.php`
- Member dashboard shell => `resources/views/member/dashboard.blade.php`

## Route groups
- guest auth routes (`/login`)
- authenticated logout route (`/logout`)
- admin group: `/admin/*` with `auth + active + role`
- member group: `/member/*` with `auth + active + role:MEMBER`

## First core migrations
- create_roles_table
- create_users_table
- create_members_table
- create_billing_cycles_table

## Controllers scaffolded
- `AuthController`
- `Admin\DashboardController`
- `Member\DashboardController`
