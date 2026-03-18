<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];
}
