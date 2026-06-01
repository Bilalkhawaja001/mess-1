<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $payload = AppSetting::publicPayload();

        if (! (bool) data_get($payload, 'mobile_app_enabled', true)) {
            return $this->disabledResponse($request, 'mobile_app');
        }

        if (! (bool) data_get($payload, 'features.'.$feature, true)) {
            return $this->disabledResponse($request, $feature);
        }

        return $next($request);
    }

    private function disabledResponse(Request $request, string $feature): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This app feature is currently disabled.',
                'feature' => $feature,
            ], 403);
        }

        return response()->view('member.feature-disabled', ['feature' => $feature], 403);
    }
}
