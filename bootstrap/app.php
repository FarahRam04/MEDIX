<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsDoctor;
use App\Http\Middleware\EnsureUserIsEmployee;
use App\Http\Middleware\is_user;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Console\Tasks\TaskServiceProvider::class,
    ])

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'is_admin'=> EnsureUserIsAdmin::class,
            'is_employee'=>EnsureUserIsEmployee::class,
            'is_user'=> is_user::class,
            'is_doctor'=>EnsureUserIsDoctor::class,
            ]);

        //
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //

    })->create();
