<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        $allowedRoutes = [
            'admin.auth.password-change.form',
            'admin.auth.password-change',
            'logout',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()
            ->route('admin.auth.password-change.form')
            ->with('warning', 'Password update required before continuing.');
    }
}
