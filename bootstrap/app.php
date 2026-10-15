<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Illuminate\Foundation\Configuration\Middleware $middleware) {
            // Add security headers to all web requests
            $middleware->web(append: [
                \App\Http\Middleware\SecurityHeaders::class,
            ]);

            // Middleware aliases
            $middleware->alias([
                'admin' => \App\Http\Middleware\AdminAuth::class,
            ]);

            // Exclude hardware detection API from CSRF verification
            $middleware->validateCsrfTokens(except: [
                '/api/hardware/detection',
            ]);
        })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
