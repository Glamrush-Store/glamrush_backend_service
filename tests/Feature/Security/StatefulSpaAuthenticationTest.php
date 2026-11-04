<?php

use App\Presentation\Http\Middleware\RequireStatefulSpaRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

it('registers Sanctum stateful middleware on the API group', function () {
    $middleware = app('router')->getMiddlewareGroups()['api'];

    expect($middleware)->toContain(EnsureFrontendRequestsAreStateful::class);
});

it('issues the CSRF cookie for a configured first party origin', function () {
    $this->withHeader('Origin', 'http://localhost')
        ->get('/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('rejects session authentication calls that are not stateful SPA requests', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'customer@example.com',
        'password' => 'password',
    ])
        ->assertStatus(400)
        ->assertJsonPath('message', 'This authentication endpoint requires a stateful SPA request.');
});

it('attaches the stateful requirement to every session creating endpoint', function (string $uri) {
    $route = collect(Route::getRoutes()->getRoutes())->first(
        fn (RoutingRoute $route): bool => $route->uri() === $uri && in_array('POST', $route->methods(), true),
    );

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('stateful.spa')
        ->and(app('router')->getMiddleware()['stateful.spa'])->toBe(RequireStatefulSpaRequest::class);
})->with([
    'api/v1/auth/login',
    'api/v1/auth/register',
    'api/v1/auth/social/{provider}',
]);
