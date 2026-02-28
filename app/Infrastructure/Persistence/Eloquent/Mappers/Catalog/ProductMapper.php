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
use DateTimeImmutable;

final class ProductMapper
{
    public static function toDomain(Product $model): ProductEntity
    {
        return new ProductEntity(
            name: $model->name,
            slug: $model->slug,
            sku: $model->sku,
            type: $model->type,
            isFeatured: (bool)$model->is_featured,
            sortOrder: (int)$model->sort_order,
            vendor: self::mapVendor($model),
            category: self::mapCategory($model),
            brand: self::mapBrand($model),
            images: $model->getMedia('catalog-photos')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'medium' => $media->getUrl('medium'),
                ];
            })->all(),
            price: (float)$model->price,
            salePrice: $model->sale_price !== null
                ? (float)$model->sale_price
                : null,
            saleStartsAt: $model->sale_starts_at,
            saleEndsAt: $model->sale_ends_at,
            manageStock: (bool)$model->manage_stock,
            stockQuantity: (int)$model->stock_quantity,
            inStock: (bool)$model->in_stock,
            variants: self::mapVariants($model)
        );
    }

    private static function mapVendor($model): ?BrandEntity
    {
        if (!$model->relationLoaded('vendor') || !$model->vendor) {
            return null;
        }

        return new BrandEntity(
            id: (string)$model->vendor->id,
            name: $model->vendor->name,
            slug: $model->vendor->slug,
        );
    }

    private static function mapCategory($model): ?CategoryEntity
    {
        if (!$model->relationLoaded('category') || !$model->category) {
            return null;
        }

        return new CategoryEntity(
            id: (string)$model->category->id,
            name: $model->category->name,
            slug: $model->category->slug,
        );
    }

    private static function mapBrand($model): ?BrandEntity
    {
        if (!$model->relationLoaded('brand') || !$model->brand) {
            return null;
        }

        return new BrandEntity(
            id: (string)$model->brand->id,
            name: $model->brand->name,
            slug: $model->brand->slug,
        );
    }

    /**
     * @return ProductVariantEntity[]
     */
    private static function mapVariants(Product $model): array
    {
        if (!$model->relationLoaded('variants')) {
            return [];
        }

        return $model->variants->map(
            fn($variant) => new ProductVariantEntity(
                id: (string)$variant->id,
                sku: $variant->sku,
                images: $variant->getMedia('catalog-photos')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'url' => $media->getUrl(),
                        'thumb' => $media->getUrl('thumb'),
                        'medium' => $media->getUrl('medium'),
                    ];
                })->all(),
                isDefault: (bool)$variant->is_default,
                price: (float)$variant->price,
                salePrice: $variant->sale_price !== null
                    ? (float)$variant->sale_price
                    : null,
                saleStartsAt: self::toDateTime($variant->sale_starts_at),
                saleEndsAt: self::toDateTime($variant->sale_ends_at),
                manageStock: (bool)$variant->manage_stock,
                stockQuantity: (int)$variant->stock_quantity,
                inStock: (bool)$variant->in_stock,
                attributes: $variant->attributes ?? [],
                sortOrder: (int)$variant->sort_order,
                status: $variant->status,

            )
        )->all();
    }

    private static function toDateTime($value): ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }
        return new DateTimeImmutable($value->toDateTimeString());
    }
}
