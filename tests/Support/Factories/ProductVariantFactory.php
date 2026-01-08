<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace Tests\Support\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;

final class ProductVariantFactory
{
    public static function make(array $overrides = []): ProductVariant
    {
        return new ProductVariant(array_merge([
            'id' => 'DBCVNGDERNMD7JDFT78H2D',
            'product_id' => '01J2P9WQZ3YJ6R8X9A2C4D5E6F',
            'sku' => 'FACE-SCRUB-LEM-099009',
            'is_default' => false,

            // persistence values only
            'price' => 4000,
            'sale_price' => null,
            'sale_starts_at' => null,
            'sale_ends_at' => null,

            'manage_stock' => true,
            'stock_quantity' => 10,

            // JSON column (casted to array in model)
            'attributes' => [
                'color' => 'red',
                'size' => 'large',
            ],

            'sort_order' => 1,
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Attach a product relation explicitly (no lazy loading)
     */
    public static function withProduct(ProductVariant $variant, $product): ProductVariant
    {
        $variant->setRelation('product', $product);

        return $variant;
    }
}
