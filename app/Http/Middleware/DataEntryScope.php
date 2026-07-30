<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DataEntryScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || optional($user->role)->code !== 'DATA_ENTRY') {
            return $next($request);
        }

        $allowedRoutes = [
            'admin.attendance.index',
            'admin.attendance.store',
            'admin.attendance-monthly.index',
            'admin.users.index',
            'admin.users.store',
            'admin.users.toggle-active',
        ];

        if (! in_array($request->route()?->getName(), $allowedRoutes, true)) {
            abort(403, 'DATA_ENTRY access is limited to Attendance and User account operations.');
        }

        return $next($request);
    }
}
