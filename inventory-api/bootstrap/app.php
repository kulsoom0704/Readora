<?php

use App\Http\Middleware\RequireReadoraAdmin;
use App\Http\Middleware\VerifyReadoraIdentity;
use App\Http\Middleware\VerifyServiceToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'service.token' => VerifyServiceToken::class,
            'trusted.identity' => VerifyReadoraIdentity::class,
            'trusted.admin' => RequireReadoraAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());
    })->create();
