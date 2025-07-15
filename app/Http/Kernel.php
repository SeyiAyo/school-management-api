<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\DebugCorsMiddleware::class,
        // \Illuminate\Http\Middleware\HandleCors::class, // Commented out for debugging
    ];

    protected $middlewareGroups = [
        'api' => [
            \Illuminate\Http\Middleware\HandleCors::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
}
