<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDataEntryAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || optional($user->role)->code !== 'DATA_ENTRY') {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        $allowed = [
            'admin.members.',
            'admin.attendance.',
            'admin.attendance-monthly.',
            'admin.auth.password-',
        ];

        foreach ($allowed as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'Data Entry access is limited to Members and Attendance.');
    }
}
