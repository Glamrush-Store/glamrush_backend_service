<?php

namespace App\Providers;

use App\Domain\Catalog\Brand\Contracts\BrandRepository;
use App\Domain\Catalog\Cart\Contracts\CartRepository;
use App\Domain\Catalog\Category\Contracts\CategoryRepository;
use App\Domain\Catalog\Product\Contracts\ProductRepository;
use App\Domain\Catalog\SavedItem\Contracts\SavedItemRepository;
use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\User\Contracts\AddressRepository;
use App\Domain\User\Contracts\SocialAccountRepository;
use App\Domain\User\Contracts\UserRepository;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAddressRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCartRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSavedItemRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShippingRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSocialAccountRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            SocialAccountRepository::class,
            EloquentSocialAccountRepository::class,
        );

        $this->app->bind(
            SavedItemRepository::class,
            EloquentSavedItemRepository::class,
        );

        $this->app->bind(
            CartRepository::class,
            EloquentCartRepository::class,
        );

        $this->app->bind(
            AddressRepository::class,
            EloquentAddressRepository::class,
        );

        $this->app->bind(
            ShippingRepository::class,
            EloquentShippingRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'category' => Category::class,
            'product' => Product::class,
            'product_variant' => ProductVariant::class,
            'user' => User::class,
        ]);
    }
}
