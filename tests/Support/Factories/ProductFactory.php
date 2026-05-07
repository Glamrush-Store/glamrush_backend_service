<?php

namespace Tests\Support\Factories;

use App\Domain\Catalog\Product\Enums\ProductStatus;
use App\Domain\Catalog\Product\Enums\ProductType;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Shared\DTOs\PriceDto;
use Illuminate\Support\Collection;

final class ProductFactory
{
    public static function make(array $overrides = []): Product
    {
        return new Product(array_merge([
            'id' => '01J2P9WQZ3YJ6R8X9A2C4D5E6F',
            'name' => 'Product Name',
            'slug' => 'product-name',
            'short_description' => 'Short description',
            'description' => 'Description',
            'type' => ProductType::VARIABLE->value,
            'status' => ProductStatus::PUBLISHED->value,
            'meta_title' => 'Meta title',
            'meta_description' => 'Meta description',
            'is_featured' => true,
            'price' => new PriceDto(
                amount: 4000,
                currency: 'NGN',
                saleAmount: 3000,
                onSale: true
            ),
        ], $overrides));
    }

    public static function withRelations(
        Product $product,
        $category,
        $brand,
        ?Collection $variants = null
    ): Product {
        $product->setRelation('category', $category);
        $product->setRelation('brand', $brand);

        if ($variants) {
            $product->setRelation('variants', $variants);
            $product->setRelation('defaultVariant', $variants->first());
        }

        return $product;
    }
}
