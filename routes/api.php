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
use App\Presentation\Http\Controllers\Customer\DeleteAddressController;
use App\Presentation\Http\Controllers\Customer\ListAddressesController;
use App\Presentation\Http\Controllers\Customer\SetDefaultAddressController;
use App\Presentation\Http\Controllers\Customer\ShowAddressController;
use App\Presentation\Http\Controllers\Customer\StoreAddressController;
use App\Presentation\Http\Controllers\Customer\UpdateAddressController;
use App\Presentation\Http\Controllers\Order\ListMyOrdersController;
use App\Presentation\Http\Controllers\Payment\InitializePaymentController;
use App\Presentation\Http\Controllers\Payment\ListPaymentMethodsController;
use App\Presentation\Http\Controllers\Payment\PaymentWebhookController;
use App\Presentation\Http\Controllers\Payment\VerifyPaymentController;
use App\Presentation\Http\Controllers\Shipping\GetShippingOptionsController;
use App\Presentation\Http\Controllers\Storefront\GetHomepageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================================
//  CATALOG API ROUTES
// ========================================================

Route::prefix('v1')->group(function () {
    // Single-root-category storefront. These routes preserve the existing
    // full catalog API while enforcing the selected root category tree.
    Route::prefix('storefronts/{storefront}')
        ->middleware('storefront.category')
        ->group(function () {
            Route::middleware('public.cache')->group(function () {
                Route::get('/homepage', GetHomepageController::class);
                Route::get('/categories', ListCategoryController::class);
                Route::get('/products', ListProductController::class);
                Route::get('/products/{slug}', GetProductController::class);
                Route::get('/collections/{collection}/products', ListProductController::class);
            });

            Route::prefix('cart')->group(function () {
                Route::post('/', AddToCartController::class);
                Route::post('/merge', MergeCartController::class)->middleware('auth:sanctum');

                Route::middleware('cart.identifier')->group(function () {
                    Route::get('/', GetCartController::class);
                    Route::patch('/items/{itemId}', UpdateCartItemByIdController::class);
                    Route::delete('/items/{itemId}', RemoveCartItemByIdController::class);
                    Route::patch('/{productId}', UpdateCartItemController::class);
                    Route::delete('/{productId}', RemoveCartItemController::class);
                    Route::delete('/', ClearCartController::class);
                });
            });

            Route::post('/checkout/cart', CheckoutCartController::class)
                ->middleware(['cart.identifier', 'idempotency.required']);
        });

    // ======================================================
    //  AUTH ROUTES
    // ======================================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', RegisterController::class);
        Route::post('/login', LoginController::class);
        Route::post('/social/{provider}', SocialCallbackController::class);
        Route::post('/password/forgot', ForgotPasswordController::class);
        Route::post('/password/verify', VerifyPasswordCodeController::class);
        Route::post('/password/reset', ResetPasswordController::class);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', LogoutController::class);
            Route::get('/me', MeController::class);
        });
    });

    // Products
    Route::middleware('public.cache')->group(function () {
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
        Route::post('/', AddToCartController::class);
        Route::post('/merge', MergeCartController::class)->middleware('auth:sanctum');

        Route::middleware('cart.identifier')->group(function () {
            Route::get('/', GetCartController::class);
            Route::patch('/items/{itemId}', UpdateCartItemByIdController::class);
            Route::delete('/items/{itemId}', RemoveCartItemByIdController::class);
            Route::patch('/{productId}', UpdateCartItemController::class);
            Route::delete('/{productId}', RemoveCartItemController::class);
            Route::delete('/', ClearCartController::class);
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
        ->middleware(['cart.identifier', 'idempotency.required']);

    Route::get('/payment-methods', ListPaymentMethodsController::class)->middleware('public.cache');
    Route::post('/payments/initialize', InitializePaymentController::class)
        ->middleware('idempotency.required');
    Route::post('/payments/verify', VerifyPaymentController::class);
    Route::post('/payments/webhooks/{provider}', PaymentWebhookController::class)
        ->whereIn('provider', ['paystack', 'flutterwave']);
});

Route::get('/test', fn () => 'test worked');
