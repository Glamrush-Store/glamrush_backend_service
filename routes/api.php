<?php

use App\Presentation\Http\Controllers\Auth\ForgotPasswordController;
use App\Presentation\Http\Controllers\Auth\LoginController;
use App\Presentation\Http\Controllers\Auth\LogoutController;
use App\Presentation\Http\Controllers\Auth\MeController;
use App\Presentation\Http\Controllers\Auth\RegisterController;
use App\Presentation\Http\Controllers\Auth\ResetPasswordController;
use App\Presentation\Http\Controllers\Auth\SocialCallbackController;
use App\Presentation\Http\Controllers\Auth\VerifyPasswordCodeController;
use App\Presentation\Http\Controllers\Catalog\AddSavedItemController;
use App\Presentation\Http\Controllers\Catalog\AddToCartController;
use App\Presentation\Http\Controllers\Catalog\ClearCartController;
use App\Presentation\Http\Controllers\Catalog\GetCartController;
use App\Presentation\Http\Controllers\Catalog\GetProductController;
use App\Presentation\Http\Controllers\Catalog\ListBrandController;
use App\Presentation\Http\Controllers\Catalog\ListCategoryController;
use App\Presentation\Http\Controllers\Catalog\ListProductController;
use App\Presentation\Http\Controllers\Catalog\ListSavedItemsController;
use App\Presentation\Http\Controllers\Catalog\MergeCartController;
use App\Presentation\Http\Controllers\Catalog\RemoveCartItemByIdController;
use App\Presentation\Http\Controllers\Catalog\RemoveCartItemController;
use App\Presentation\Http\Controllers\Catalog\RemoveSavedItemController;
use App\Presentation\Http\Controllers\Catalog\SyncSavedItemsController;
use App\Presentation\Http\Controllers\Catalog\UpdateCartItemByIdController;
use App\Presentation\Http\Controllers\Catalog\UpdateCartItemController;
use App\Presentation\Http\Controllers\Checkout\CheckoutCartController;
use App\Presentation\Http\Controllers\Contact\StoreContactSubmissionController;
use App\Presentation\Http\Controllers\Content\ListPublicFaqsController;
use App\Presentation\Http\Controllers\Content\ShowPublicContentPageController;
use App\Presentation\Http\Controllers\Customer\DeleteAddressController;
use App\Presentation\Http\Controllers\Customer\ListAddressesController;
use App\Presentation\Http\Controllers\Customer\SetDefaultAddressController;
use App\Presentation\Http\Controllers\Customer\ShowAddressController;
use App\Presentation\Http\Controllers\Customer\StoreAddressController;
use App\Presentation\Http\Controllers\Customer\UpdateAddressController;
use App\Presentation\Http\Controllers\Discount\ValidateDiscountController;
use App\Presentation\Http\Controllers\Newsletter\ConfirmNewsletterSubscriptionController;
use App\Presentation\Http\Controllers\Newsletter\ResendNewsletterConfirmationController;
use App\Presentation\Http\Controllers\Newsletter\SubscribeNewsletterController;
use App\Presentation\Http\Controllers\Newsletter\UnsubscribeNewsletterController;
use App\Presentation\Http\Controllers\Order\ListMyOrdersController;
use App\Presentation\Http\Controllers\Payment\InitializePaymentController;
use App\Presentation\Http\Controllers\Payment\ListPaymentMethodsController;
use App\Presentation\Http\Controllers\Payment\PaymentWebhookController;
use App\Presentation\Http\Controllers\Payment\VerifyPaymentController;
use App\Presentation\Http\Controllers\Shipping\GetShippingOptionsController;
use App\Presentation\Http\Controllers\Storefront\GetHomepageController;
use App\Presentation\Http\Controllers\Storefront\GetStorefrontConfigurationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================================
//  CATALOG API ROUTES
// ========================================================

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::prefix('newsletter/subscriptions')->group(function () {
        Route::post('/', SubscribeNewsletterController::class)->middleware('throttle:newsletter-subscribe');
        Route::get('/confirm/{token}', ConfirmNewsletterSubscriptionController::class)
            ->middleware('throttle:newsletter-action')
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('newsletter.subscriptions.confirm');
        Route::post('/resend-confirmation', ResendNewsletterConfirmationController::class)
            ->middleware('throttle:newsletter-subscribe');
        Route::post('/unsubscribe', UnsubscribeNewsletterController::class)
            ->middleware('throttle:newsletter-action');
    });

    // Single-root-category storefront. These routes preserve the existing
    // full catalog API while enforcing the selected root category tree.
    Route::prefix('storefronts/{storefront}')
        ->middleware('storefront.category')
        ->group(function () {
            Route::middleware(['public.cache', 'throttle:catalog'])->group(function () {
                Route::get('/homepage', GetHomepageController::class);
                Route::get('/categories', ListCategoryController::class);
                Route::get('/products', ListProductController::class);
                Route::get('/products/{slug}', GetProductController::class);
                Route::get('/collections/{collection}/products', ListProductController::class);
            });

            Route::get('/configuration', GetStorefrontConfigurationController::class)
                ->middleware('throttle:catalog');

            Route::get('/pages/{slug}', ShowPublicContentPageController::class)
                ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
            Route::get('/faqs', ListPublicFaqsController::class);
            Route::post('/contact-submissions', StoreContactSubmissionController::class)
                ->middleware('throttle:contact-submission');

            Route::prefix('cart')->group(function () {
                Route::post('/', AddToCartController::class)->middleware('throttle:cart-mutation');
                Route::post('/merge', MergeCartController::class)
                    ->middleware(['auth:sanctum', 'throttle:cart-mutation']);

                Route::middleware('cart.identifier')->group(function () {
                    Route::get('/', GetCartController::class);
                    Route::patch('/items/{itemId}', UpdateCartItemByIdController::class)
                        ->middleware('throttle:cart-mutation');
                    Route::delete('/items/{itemId}', RemoveCartItemByIdController::class)
                        ->middleware('throttle:cart-mutation');
                    Route::patch('/{productId}', UpdateCartItemController::class)
                        ->middleware('throttle:cart-mutation');
                    Route::delete('/{productId}', RemoveCartItemController::class)
                        ->middleware('throttle:cart-mutation');
                    Route::delete('/', ClearCartController::class)->middleware('throttle:cart-mutation');
                });
            });

            Route::post('/checkout/cart', CheckoutCartController::class)
                ->middleware(['cart.identifier', 'idempotency.required', 'throttle:checkout-payment']);
            Route::post('/discounts/validate', ValidateDiscountController::class)
                ->middleware(['cart.identifier', 'throttle:checkout-payment']);
        });

    // ======================================================
    //  AUTH ROUTES
    // ======================================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', RegisterController::class)
            ->middleware(['stateful.spa', 'throttle:onboarding']);
        Route::post('/login', LoginController::class)
            ->middleware(['stateful.spa', 'throttle:login']);
        Route::post('/social/{provider}', SocialCallbackController::class)
            ->middleware(['stateful.spa', 'throttle:onboarding']);
        Route::post('/password/forgot', ForgotPasswordController::class)->middleware('throttle:password-forgot');
        Route::post('/password/verify', VerifyPasswordCodeController::class)->middleware('throttle:password-verify');
        Route::post('/password/reset', ResetPasswordController::class)->middleware('throttle:password-reset');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', LogoutController::class);
            Route::get('/me', MeController::class);
        });
    });

    // Products
    Route::middleware(['public.cache', 'throttle:catalog'])->group(function () {
        Route::get('/products', ListProductController::class);
        Route::get('/products/{slug}', GetProductController::class);

        // Collections
        Route::get('/collections/{collection}/products', ListProductController::class);

        // Categories
        Route::get('/categories', ListCategoryController::class);

        // Brands
        Route::get('/brands', ListBrandController::class);
    });

    //    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    // Cart
    Route::prefix('cart')->group(function () {
        Route::post('/', AddToCartController::class)->middleware('throttle:cart-mutation');
        Route::post('/merge', MergeCartController::class)
            ->middleware(['auth:sanctum', 'throttle:cart-mutation']);

        Route::middleware('cart.identifier')->group(function () {
            Route::get('/', GetCartController::class);
            Route::patch('/items/{itemId}', UpdateCartItemByIdController::class)
                ->middleware('throttle:cart-mutation');
            Route::delete('/items/{itemId}', RemoveCartItemByIdController::class)
                ->middleware('throttle:cart-mutation');
            Route::patch('/{productId}', UpdateCartItemController::class)
                ->middleware('throttle:cart-mutation');
            Route::delete('/{productId}', RemoveCartItemController::class)
                ->middleware('throttle:cart-mutation');
            Route::delete('/', ClearCartController::class)->middleware('throttle:cart-mutation');
        });
    });

    // Saved Items
    Route::middleware('auth:sanctum')->prefix('saved-items')->group(function () {
        Route::get('/', ListSavedItemsController::class);
        Route::post('/', AddSavedItemController::class);
        Route::post('/sync', SyncSavedItemsController::class);
        Route::delete('/{productId}', RemoveSavedItemController::class);
    });

    // Customer Addresses
    Route::middleware('auth:sanctum')->prefix('addresses')->group(function () {
        Route::get('/', ListAddressesController::class);
        Route::post('/', StoreAddressController::class);
        Route::get('/{address}', ShowAddressController::class);
        Route::patch('/{address}', UpdateAddressController::class);
        Route::delete('/{address}', DeleteAddressController::class);
        Route::patch('/{address}/default', SetDefaultAddressController::class);
    });

    Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
        Route::get('/', ListMyOrdersController::class);
    });

    Route::prefix('shipping')->group(function () {
        Route::post('/getoptions', GetShippingOptionsController::class);
    });

    // Checkout
    Route::post('/checkout/cart', CheckoutCartController::class)
        ->middleware(['cart.identifier', 'idempotency.required', 'throttle:checkout-payment']);

    Route::get('/payment-methods', ListPaymentMethodsController::class)->middleware('public.cache');
    Route::post('/payments/initialize', InitializePaymentController::class)
        ->middleware(['idempotency.required', 'throttle:checkout-payment']);
    Route::post('/payments/verify', VerifyPaymentController::class)->middleware('throttle:payment-verify');
    Route::post('/payments/webhooks/{provider}', PaymentWebhookController::class)
        ->middleware('throttle:payment-webhook')
        ->whereIn('provider', ['paystack', 'flutterwave']);
});

Route::get('/test', fn () => 'test worked');
