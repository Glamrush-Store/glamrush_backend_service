<?php

use App\Features\Brand\BrandController;
use App\Features\Category\CategoryController;
use App\Features\Product\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    //Products
    Route::get('/products', [ProductController::class, 'index']);

    Route::get('/products/{slug}', [ProductController::class, 'show']);

    //Categories
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/categories/{slug}', [CategoryController::class, 'show']);


    //Brands
    Route::get('/brands', [BrandController::class, 'index']);

    Route::get('/brands/{slug}', [BrandController::class, 'show']);
});


Route::get('/test', fn() => 'test worked');
