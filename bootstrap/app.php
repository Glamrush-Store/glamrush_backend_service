<?php

use App\Presentation\Http\Middleware\RequireCartIdentifier;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(App\Presentation\Http\Middleware\ForceJsonResponse::class);

        $middleware->alias([
            'cart.identifier' => RequireCartIdentifier::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e): JsonResponse {
            return new JsonResponse(['message' => 'Unauthorized.'], 401);
        });
    })->create();
