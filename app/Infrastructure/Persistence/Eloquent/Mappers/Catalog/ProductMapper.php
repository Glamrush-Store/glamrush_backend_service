<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Catalog;

use App\Domain\Catalog\Brand\Entities\BrandEntity;
use App\Domain\Catalog\Category\Entities\CategoryEntity;
use App\Domain\Catalog\Product\Entities\ProductEntity;
use App\Domain\Catalog\Product\Entities\ProductVariantEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Support\Media\SafeMediaUrl;
use DateTimeImmutable;

final class ProductMapper
{
    public static function toDomain(Product $model): ProductEntity
    {
        return new ProductEntity(
            id: $model->id,
            name: $model->name,
            slug: $model->slug,
            sku: self::mapSku($model),
            type: $model->type,
            isFeatured: (bool) $model->is_featured,
            sortOrder: (int) $model->sort_order,
            vendor: self::mapVendor($model),
            category: self::mapCategory($model),
            categories: self::mapCategories($model),
            brand: self::mapBrand($model),
            images: $model->getMedia('catalog-photos')
                ->map(fn ($media) => SafeMediaUrl::image($media))
                ->all(),
            price: (float) $model->price,
            salePrice: $model->sale_price !== null
                ? (float) $model->sale_price
                : null,
            saleStartsAt: $model->sale_starts_at,
            saleEndsAt: $model->sale_ends_at,
            manageStock: (bool) $model->manage_stock,
            stockQuantity: (int) $model->stock_quantity,
            inStock: (bool) $model->in_stock,
            variants: self::mapVariants($model),
            shortDescription: $model->short_description,
            description: $model->description,
            metaTitle: $model->meta_title,
            metaDescription: $model->meta_description,
        );
    }

    private static function mapVendor($model): ?BrandEntity
    {
        if (! $model->relationLoaded('vendor') || ! $model->vendor) {
            return null;
        }

        return new BrandEntity(
            id: (string) $model->vendor->id,
            name: $model->vendor->name,
            slug: $model->vendor->slug,
        );
    }

    private static function mapSku(Product $model): ?string
    {
        if ($model->sku) {
            return $model->sku;
        }

        if ($model->type !== 'simple' || ! $model->relationLoaded('variants')) {
            return null;
        }

        $variant = $model->variants->firstWhere('is_default', true)
            ?? $model->variants->first();

        return $variant?->sku;
    }

    private static function mapCategory($model): ?CategoryEntity
    {
        if ($model->relationLoaded('primaryCategory') && $model->primaryCategory->isNotEmpty()) {
            return self::categoryEntity($model->primaryCategory->first());
        }

        if ($model->relationLoaded('categories') && $model->categories->isNotEmpty()) {
            $primary = $model->categories->first(fn ($category) => (bool) ($category->pivot?->is_primary));

            return self::categoryEntity($primary ?? $model->categories->first());
        }

        return null;
    }

    /** @return CategoryEntity[] */
    private static function mapCategories($model): array
    {
        if (! $model->relationLoaded('categories')) {
            return [];
        }

        return $model->categories
            ->map(fn ($category) => self::categoryEntity($category))
            ->all();
    }

    private static function categoryEntity($category): CategoryEntity
    {
        return new CategoryEntity(
            id: (string) $category->id,
            name: $category->name,
            slug: $category->slug,
        );
    }

    private static function mapBrand($model): ?BrandEntity
    {
        if (! $model->relationLoaded('brand') || ! $model->brand) {
            return null;
        }

        return new BrandEntity(
            id: (string) $model->brand->id,
            name: $model->brand->name,
            slug: $model->brand->slug,
        );
    }

    /**
     * @return ProductVariantEntity[]
     */
    private static function mapVariants(Product $model): array
    {
        if (! $model->relationLoaded('variants')) {
            return [];
        }

        return $model->variants->map(
            fn ($variant) => new ProductVariantEntity(
                id: (string) $variant->id,
                sku: $variant->sku,
                images: $variant->getMedia('catalog-photos')
                    ->map(fn ($media) => SafeMediaUrl::image($media))
                    ->all(),
                isDefault: (bool) $variant->is_default,
                price: (float) $variant->price,
                salePrice: $variant->sale_price !== null
                    ? (float) $variant->sale_price
                    : null,
                saleStartsAt: self::toDateTime($variant->sale_starts_at),
                saleEndsAt: self::toDateTime($variant->sale_ends_at),
                manageStock: (bool) $variant->manage_stock,
                stockQuantity: (int) $variant->stock_quantity,
                inStock: (bool) $variant->in_stock,
                attributes: $variant->attributes ?? [],
                sortOrder: (int) $variant->sort_order,
                status: $variant->status,

            )
        )->all();
    }

    private static function toDateTime($value): ?DateTimeImmutable
    {
        if (! $value) {
            return null;
        }

        return new DateTimeImmutable($value->toDateTimeString());
    }
}
