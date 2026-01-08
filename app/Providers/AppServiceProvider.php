<?php

namespace App\Providers;

use App\Features\Brand\Contracts\BrandRepository;
use App\Features\Category\Contracts\CategoryRepository;
use App\Features\Product\Contracts\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentBrandRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProductRepository::class,
            EloquentProductRepository::class,
        );
        $this->app->bind(
            CategoryRepository::class,
            EloquentCategoryRepository::class,
        );

        $this->app->bind(
            BrandRepository::class,
            EloquentBrandRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
