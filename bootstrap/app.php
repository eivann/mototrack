<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
       
       $middleware->alias([
        'owner' => \App\Http\Middleware\OwnerMiddleware::class,
        'otp.verified' => \App\Http\Middleware\EnsureOtpVerified::class,
        'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
       ]);

       $middleware->web(append: [
        \App\Http\Middleware\EnsureOtpVerified::class,
        \App\Http\Middleware\EnsurePasswordChanged::class,
       ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
