<?php

use App\Shared\Security\ApiRateLimitKey;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

it('limits login attempts by both normalized email and IP address', function () {
    Route::post('/_security/login', fn () => response()->json(['success' => true]))
        ->middleware('throttle:login');

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/_security/login', [
                'email' => 'Customer@Example.com',
                'password' => 'incorrect-password',
            ])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
        ->postJson('/_security/login', [
            'email' => 'customer@example.com',
            'password' => 'incorrect-password',
        ])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('message', 'Too many requests. Please try again later.');
});

it('limits credential stuffing across different emails from one IP address', function () {
    Route::post('/_security/login', fn () => response()->json(['success' => true]))
        ->middleware('throttle:login');

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->postJson('/_security/login', [
                'email' => "customer{$attempt}@example.com",
                'password' => 'incorrect-password',
            ])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->postJson('/_security/login', [
            'email' => 'another@example.com',
            'password' => 'incorrect-password',
        ])
        ->assertStatus(429);
});

it('limits password reset requests per email and per IP address', function () {
    Route::post('/_security/password-forgot', fn () => response()->json(['success' => true]))
        ->middleware('throttle:password-forgot');

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.30'])
            ->postJson('/_security/password-forgot', ['email' => 'missing@example.com'])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.31'])
        ->postJson('/_security/password-forgot', ['email' => 'missing@example.com'])
        ->assertStatus(429);
});

it('limits password reset requests across emails from one IP address', function () {
    Route::post('/_security/password-forgot', fn () => response()->json(['success' => true]))
        ->middleware('throttle:password-forgot');

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->postJson('/_security/password-forgot', [
                'email' => "missing{$attempt}@example.com",
            ])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
        ->postJson('/_security/password-forgot', ['email' => 'another@example.com'])
        ->assertStatus(429);
});

it('allows five code attempts per password reset generation', function () {
    Route::post('/_security/password-verify', fn () => response()->json(['success' => true]))
        ->middleware('throttle:password-verify');

    $email = 'customer@example.com';
    $cacheKey = ApiRateLimitKey::passwordResetGenerationCacheKey($email);
    Cache::put($cacheKey, 'generation-one', now()->addMinutes(15));

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/_security/password-verify', ['email' => $email])->assertOk();
    }

    $this->postJson('/_security/password-verify', ['email' => $email])->assertStatus(429);

    Cache::put($cacheKey, 'generation-two', now()->addMinutes(15));
    $this->postJson('/_security/password-verify', ['email' => $email])->assertOk();
});

it('shares the onboarding allowance between registration and social authentication', function () {
    Route::post('/_security/register', fn () => response()->json(['success' => true]))
        ->middleware('throttle:onboarding');
    Route::post('/_security/social', fn () => response()->json(['success' => true]))
        ->middleware('throttle:onboarding');

    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $this->postJson('/_security/register')->assertOk();
    }

    $this->postJson('/_security/social')->assertOk();
    $this->postJson('/_security/social')->assertStatus(429);
});

it('shares the checkout and payment initialization allowance per customer', function () {
    Route::post('/_security/checkout', fn () => response()->json(['success' => true]))
        ->middleware('throttle:checkout-payment');
    Route::post('/_security/payment-initialize', fn () => response()->json(['success' => true]))
        ->middleware('throttle:checkout-payment');

    $headers = ['X-Cart-Token' => '019fca80-2f9a-7d51-98f4-6d1977669abd'];

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $this->postJson('/_security/checkout', [], $headers)->assertOk();
    }

    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $this->postJson('/_security/payment-initialize', [], $headers)->assertOk();
    }

    $this->postJson('/_security/payment-initialize', [], $headers)->assertStatus(429);
});

it('limits payment verification by reference even when the IP changes', function () {
    Route::post('/_security/payment-verify', fn () => response()->json(['success' => true]))
        ->middleware('throttle:payment-verify');

    $payload = ['provider' => 'flutterwave', 'transaction_id' => 'transaction-100'];

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$attempt}"])
            ->postJson('/_security/payment-verify', $payload)
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.200'])
        ->postJson('/_security/payment-verify', $payload)
        ->assertStatus(429);
});

it('configures the requested limits for general, catalog, customer, payment and webhook traffic', function () {
    $request = Request::create('/webhooks/paystack', 'POST', [
        'provider' => 'paystack',
        'transaction_id' => 'transaction-1',
    ]);
    $request->headers->set('X-Cart-Token', '019fca80-2f9a-7d51-98f4-6d1977669abd');
    $route = new RoutingRoute('POST', '/webhooks/{provider}', fn () => null);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $attempts = fn (string $name): array => Collection::wrap(
        RateLimiter::limiter($name)($request),
    )->pluck('maxAttempts')->all();

    expect($attempts('api'))->toBe([120])
        ->and($attempts('catalog'))->toBe([300])
        ->and($attempts('onboarding'))->toBe([5])
        ->and($attempts('cart-mutation'))->toBe([30])
        ->and($attempts('checkout-payment'))->toBe([10])
        ->and($attempts('payment-verify'))->toBe([10, 10])
        ->and($attempts('payment-webhook'))->toBe([120, 600])
        ->and($attempts('newsletter-subscribe'))->toBe([3, 10])
        ->and($attempts('newsletter-action'))->toBe([10]);
});

it('does not let the general limiter lower the catalog allowance', function () {
    Route::get('/_security/catalog', fn () => response()->json(['success' => true]))
        ->middleware(['throttle:api', 'throttle:catalog']);

    $request = Request::create('/_security/catalog', 'GET');
    $route = collect(Route::getRoutes()->getRoutes())->first(
        fn (RoutingRoute $route): bool => $route->uri() === '_security/catalog',
    );
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $limits = Collection::wrap(RateLimiter::limiter('api')($request));

    expect($limits->pluck('maxAttempts')->all())->toBe([300]);
});

it('attaches the named limiters to their real API routes', function (
    string $method,
    string $uri,
    array $middleware,
) {
    $route = collect(Route::getRoutes()->getRoutes())->first(
        fn (RoutingRoute $route): bool => $route->uri() === $uri && in_array($method, $route->methods(), true),
    );

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain(...$middleware);
})->with([
    'login' => ['POST', 'api/v1/auth/login', ['throttle:api', 'throttle:login']],
    'registration' => ['POST', 'api/v1/auth/register', ['throttle:api', 'throttle:onboarding']],
    'social authentication' => ['POST', 'api/v1/auth/social/{provider}', ['throttle:api', 'throttle:onboarding']],
    'password reset request' => ['POST', 'api/v1/auth/password/forgot', ['throttle:api', 'throttle:password-forgot']],
    'password code verification' => ['POST', 'api/v1/auth/password/verify', ['throttle:api', 'throttle:password-verify']],
    'cart mutation' => ['POST', 'api/v1/cart', ['throttle:api', 'throttle:cart-mutation']],
    'checkout' => ['POST', 'api/v1/checkout/cart', ['throttle:api', 'throttle:checkout-payment']],
    'payment initialization' => ['POST', 'api/v1/payments/initialize', ['throttle:api', 'throttle:checkout-payment']],
    'payment verification' => ['POST', 'api/v1/payments/verify', ['throttle:api', 'throttle:payment-verify']],
    'payment webhook' => ['POST', 'api/v1/payments/webhooks/{provider}', ['throttle:api', 'throttle:payment-webhook']],
    'catalog search' => ['GET', 'api/v1/products', ['throttle:api', 'throttle:catalog']],
    'newsletter subscription' => ['POST', 'api/v1/newsletter/subscriptions', ['throttle:api', 'throttle:newsletter-subscribe']],
    'newsletter confirmation' => ['GET', 'api/v1/newsletter/subscriptions/confirm/{token}', ['throttle:api', 'throttle:newsletter-action']],
]);
