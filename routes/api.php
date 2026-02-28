<?php

use App\Presentation\Http\Controllers\Auth\ForgotPasswordController;
use App\Presentation\Http\Controllers\Auth\LoginController;
use App\Presentation\Http\Controllers\Auth\LogoutController;
use App\Presentation\Http\Controllers\Auth\MeController;
use App\Presentation\Http\Controllers\Auth\RegisterController;
use App\Presentation\Http\Controllers\Auth\ResetPasswordController;
use App\Presentation\Http\Controllers\Auth\SocialCallbackController;
use App\Presentation\Http\Controllers\Auth\VerifyPasswordCodeController;
use App\Presentation\Http\Controllers\Catalog\GetProductController;
use App\Presentation\Http\Controllers\Catalog\ListBrandController;
use App\Presentation\Http\Controllers\Catalog\ListCategoryController;
use App\Presentation\Http\Controllers\Catalog\ListProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// ========================================================
//  CATALOG API ROUTES
// ========================================================


Route::prefix('v1')->group(function () {
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

    //Products
    Route::get('/products', ListProductController::class);

    Route::get('/products/{slug}', GetProductController::class);

    //Categories
    Route::get('/categories', ListCategoryController::class);

//    Route::get('/categories/{slug}', [CategoryController::class, 'show']);


    //Brands
    Route::get('/brands', ListBrandController::class);
});


Route::get('/test', fn() => 'test worked');
