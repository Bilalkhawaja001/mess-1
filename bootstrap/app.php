<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'jazzcash/return',
            'jazzcash/ipn',
            'api/member/*',
        ]);

        $middleware->alias([
            'force_password_change' => \App\Http\Middleware\ForcePasswordChange::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'data_entry_scope' => \App\Http\Middleware\DataEntryScope::class,
            'must_change_password' => \App\Http\Middleware\RequirePasswordChange::class,
            'app_feature' => \App\Http\Middleware\EnsureAppFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 429) {
                $message = 'Too many attempts. Please wait a minute and try again.';

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => $message,
                        'retry_after' => 60,
                    ], 429);
                }

                return back()->with('error', $message);
            }

            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json(["message" => "Session expired. Please refresh and try again."], 419);
            }

            return redirect()
                ->route("login")
                ->withCookie(cookie()->forget(config("session.cookie")))
                ->with("warning", "Your session expired. Please login again.");
        });
    })->create();
