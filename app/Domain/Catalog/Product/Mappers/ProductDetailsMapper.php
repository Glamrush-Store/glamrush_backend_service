<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Mappers;

use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Category\DTOs\CategoryParentDto;
use App\Domain\Catalog\Product\DTOs\ProductDetailDto;
use App\Domain\Catalog\Product\DTOs\ProductVariantDto;
use App\Domain\Catalog\Product\Enums\ProductStatus;
use App\Domain\Catalog\Product\Enums\ProductType;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Shared\DTOs\PriceDto;

final class ProductDetailsMapper
{
    public static function fromModel(Product $product): ProductDetailDto
    {
        $variants = $product->variants
            ->map(fn(ProductVariant $variant) => ProductVariantMapper::fromModel($variant)
            )
            ->toArray();

        //$defaultVariant = self::resolveDefaultVariant($variants);

        return new ProductDetailDto(
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
                isActive: $product->category?->is_active ?? false,
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
            status: ProductStatus::from($product->status),
            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,
            isFeatured: $product->is_featured,
            variants: $variants,
            price: new PriceDto(
                amount: $product->defaultVariant->price,
                currency: 'NGN',
                saleAmount: $product->defaultVariant->sale_price,
                onSale: true
            )
        );
    }

    private static function resolveDefaultVariant(array $variants): ?ProductVariantDto
    {
        foreach ($variants as $variant) {
            if ($variant->isDefault) {
                return $variant;
            }
        }
        return null;
    }
}
