<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TrackTrafficSource::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // _fbp/_fbc are set by fbevents.js (not Laravel-encrypted); tf_* are
        // first-party attribution cookies stored as plain JSON (002 US10).
        // Without the exclusion $request->cookie() decrypts them to null.
        $middleware->encryptCookies(except: ['_fbp', '_fbc', 'tf_first', 'tf_last']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
