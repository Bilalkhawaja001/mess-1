# LIVE_SERVE_FIX_REPORT

Date: 2026-03-18 (Asia/Karachi)
Target: `C:\Users\Bilal\clawd\mess_billing_laravel_app`

## Objective
Unblock local HTTP serving/bind and run app in browser-accessible mode for P0 UAT rechecks.

## Bind/serve diagnosis

1. `php` on PATH was unavailable in this shell context.
   - `php -v` => command not found
   - explicit binary worked: `C:\php-8.5.4-nts-Win32-vs17-x64\php.exe`

2. Earlier bind failures are consistent with port contention on attempted port(s).
   - During diagnosis, port occupancy was observed (`netstat -ano` showed listener on `127.0.0.1:8000`).

3. Additional runtime blocker discovered: `artisan serve` child HTTP runtime did not reliably carry required SQLite driver setup in this environment, producing request-time DB driver failures (`could not find driver`) during UAT paths.

## Host/port combinations tested

### `artisan serve`
- `127.0.0.1:8000` -> inconsistent in environment (historically blocked by listen conflicts).
- `127.0.0.1:8081` -> bind starts, but request-path reliability was impacted by runtime extension behavior in this setup.

### Alternative Laravel-valid serve method (used for UAT)
Command used (working):

```powershell
C:\php-8.5.4-nts-Win32-vs17-x64\php.exe \
  -d extension_dir='C:/php-8.5.4-nts-Win32-vs17-x64/ext' \
  -d extension=pdo_sqlite -d extension=sqlite3 \
  -S 127.0.0.1:8081 -t public public/index.php
```

Also verified:

```powershell
... -S 0.0.0.0:8082 -t public public/index.php
```

## Browser-accessibility evidence

- `http://127.0.0.1:8081/login` => HTTP 200
- `http://localhost:8081/login` => HTTP 200
- `http://127.0.0.1:8082/login` => HTTP 200
- `http://localhost:8082/login` => HTTP 200

## Conclusion
Serve path is unblocked using explicit PHP binary + explicit extension loading + `php -S` fallback. App is reachable in browser-accessible mode on tested URLs above.