<?php

namespace App\Providers;

use App\Domain\Catalog\Brand\Contracts\BrandRepository;
use App\Domain\Catalog\Cart\Contracts\CartRepository;
use App\Domain\Catalog\Category\Contracts\CategoryRepository;
use App\Domain\Catalog\Product\Contracts\ProductRepository;
use App\Domain\Catalog\SavedItem\Contracts\SavedItemRepository;
use App\Domain\Order\Contracts\CheckoutRepository;
use App\Domain\Order\Contracts\OrderRepository;
use App\Domain\Order\Events\OrderPaid;
use App\Domain\Payment\Contracts\PaymentMethodRepository;
use App\Domain\Payment\Contracts\PaymentRepository;
use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\User\Contracts\AddressRepository;
use App\Domain\User\Contracts\SocialAccountRepository;
use App\Domain\User\Contracts\UserRepository;
use App\Listeners\Order\CommitReservedInventory;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAddressRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCartRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCheckoutRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSavedItemRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShippingRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSocialAccountRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
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

        $this->app->bind(
            CheckoutRepository::class,
            EloquentCheckoutRepository::class,
        );

        $this->app->bind(
            OrderRepository::class,
            EloquentOrderRepository::class,
        );

        $this->app->bind(
            PaymentMethodRepository::class,
            EloquentPaymentMethodRepository::class,
        );

        $this->app->bind(
            PaymentRepository::class,
            EloquentPaymentRepository::class,
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

        Event::listen(OrderPaid::class, CommitReservedInventory::class);
    }
}
