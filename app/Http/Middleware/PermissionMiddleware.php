<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        foreach ($permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                abort(403, 'Permission denied: '.$permission);
            }
        }

        return $next($request);
    }
}
