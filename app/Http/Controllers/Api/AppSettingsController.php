<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class AppSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(AppSetting::publicPayload());
    }
}
