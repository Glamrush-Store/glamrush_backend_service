<?php

use App\Presentation\Http\Middleware\PublicResponseCache;
use App\Presentation\Http\Middleware\RequireCartIdentifier;
use App\Presentation\Http\Middleware\RequireIdempotencyKey;
use App\Presentation\Http\Middleware\RequireStatefulSpaRequest;
use App\Presentation\Http\Middleware\ResolveStorefrontCategory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->prepend(App\Presentation\Http\Middleware\ApplyRuntimeSettings::class);
        $middleware->append(App\Presentation\Http\Middleware\ForceJsonResponse::class);

        $middleware->alias([
            'cart.identifier' => RequireCartIdentifier::class,
            'storefront.category' => ResolveStorefrontCategory::class,
            'public.cache' => PublicResponseCache::class,
            'idempotency.required' => RequireIdempotencyKey::class,
            'stateful.spa' => RequireStatefulSpaRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e): JsonResponse {
            return new JsonResponse(['message' => 'Unauthorized.'], 401);
        });
    })->create();
