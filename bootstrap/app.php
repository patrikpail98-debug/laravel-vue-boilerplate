<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\LaravelImageOptimizer\Middlewares\OptimizeImages;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Baseline security headers on every response (see SecurityHeaders).
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'optimizeImages' => OptimizeImages::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every api/* request is JSON-only (no HTML fallback exists for it), so
        // always render exceptions as JSON there - regardless of the request's
        // Accept header - letting the default status-code mapping apply (422 +
        // field errors for ValidationException, 404 for a missing model/route,
        // 401 for AuthenticationException, etc.) instead of a redirect to '/'.
        //
        // This used to live in App\Exceptions\Handler's shouldReturnJson()
        // override, but Laravel's withExceptions() always binds the framework's
        // own base Handler (see ApplicationBuilder::withExceptions()) - that
        // class was never actually instantiated, so the override never ran.
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());

        $exceptions->dontFlash(['password', 'password_confirmation']);
    })->create();
