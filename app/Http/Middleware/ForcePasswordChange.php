<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! (bool) $user->must_change_password) {
            return $next($request);
        }

        if (
            $request->routeIs('password.force.*') ||
            $request->routeIs('logout')
        ) {
            return $next($request);
        }

        return redirect()->route('password.force.edit');
    }
}
