<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            abort(403, 'Unauthorized role access.');
        }

        $roleCode = strtoupper((string) $user->role->code);
        $allowed = array_map(fn ($r) => strtoupper(trim($r)), $allowedRoles);

        if (! in_array($roleCode, $allowed, true)) {
            abort(403, 'Insufficient role permission.');
        }

        return $next($request);
    }
}
