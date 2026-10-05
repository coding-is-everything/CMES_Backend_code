<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\AdminPermission;
use App\Http\Middleware\CustomerAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (
        Middleware $middleware
    ) {

        $middleware->alias([
            'customer.auth' =>
            CustomerAuthenticate::class,
            'admin.auth' =>
            AdminAuthenticate::class,
            'admin.permission' =>
            AdminPermission::class,
        ]);

    })
    ->withExceptions(function (
        Exceptions $exceptions
    ) {
        //
    })
    ->create();
