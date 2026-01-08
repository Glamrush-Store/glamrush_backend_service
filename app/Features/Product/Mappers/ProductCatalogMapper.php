<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Product\Mappers;

use App\Features\Brand\DTOs\BrandDto;
use App\Features\Category\DTOs\CategoryDto;
use App\Features\Category\DTOs\CategoryParentDto;
use App\Features\Product\DTOs\ProductCatalogDto;
use App\Features\Product\Enums\ProductType;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Shared\DTOs\PriceDto;

final class ProductCatalogMapper
{
    public static function fromModel(Product $product): ProductCatalogDto
    {
        return new ProductCatalogDto(
            id: (string)$product->id,
            name: $product->name,
            slug: $product->slug,
            category: new CategoryDto(
                id: $product->category->id,
                name: $product->category->name,
                parent: new CategoryParentDto(
                    id: $product->category->parent_id ?? null,
                    name: $product->category->parent->name ?? null,
                    slug: $product->category->parent->slug ?? null
                ),
                slug: $product->category->slug,
                description: $product->category->description ?? null,
                metaTitle: $product->category->meta_title ?? null,
                metaDescription: $product->category->meta_description ?? null,
                sortOrder: $product->category->sort_order ?? null,
                isActive: $product->category->is_active ?? false,
            ),
            brand: new BrandDto(
                id: $product->brand->id,
                name: $product->brand->name,
                slug: $product->brand->slug,
                description: $product->brand->description ?? null,
                metaTitle: $product->brand->meta_title ?? null,
                metaDescription: $product->brand->meta_description ?? null,
                sortOrder: $product->brand->sort_order ?? null,
                isActive: $product->brand->is_active ?? false
            ),
            shortDescription: $product->short_description,
            description: $product->description,
            type: ProductType::from($product->type),
            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,
            isFeatured: $product->is_featured,
            price: new PriceDto(
                amount: $product->defaultVariant->price,
                currency: 'NGN',
                saleAmount: $product->defaultVariant->sale_price,
                onSale: true
            )
        );
    }

}
