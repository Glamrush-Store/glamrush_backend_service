<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

use App\Features\Product\DTOs\ProductVariantDto;
use App\Features\Product\Mappers\ProductVariantMapper;
use App\Shared\DTOs\PriceDto;
use Tests\Support\Factories\ProductVariantFactory;

it('maps product variant to dto with correct meaning', function () {
    // Arrange
    $variant = ProductVariantFactory::make([
        'manage_stock' => true,
        'stock_quantity' => 10,
        'sale_price' => null,
        'sale_starts_at' => null,
        'sale_ends_at' => null,
        'attributes' => ['color' => 'red', 'size' => 'large'],
    ]);

    // Act
    $dto = ProductVariantMapper::fromModel($variant);

    // Assert – identity
    expect($dto)->toBeInstanceOf(ProductVariantDto::class)
        ->and($dto->id)->toBe((string)$variant->id)
        ->and($dto->productId)->toBe((string)$variant->product_id)
        ->and($dto->sku)->toBe($variant->sku)
        ->and($dto->isDefault)->toBe($variant->is_default)
        //price meaning
        ->and($dto->price)->toBeInstanceOf(PriceDto::class)
        ->and($dto->price->amount)->toBe($variant->price)
        ->and($dto->price->onSale)->toBeFalse()
        ->and($dto->salePrice)->toBeNull()

        //stock meaning
        ->and($dto->manageStock)->toBeTrue()
        ->and($dto->stockQuantity)->toBe(10)
        ->and($dto->inStock)->toBeTrue()

        //misc
        ->and($dto->variantAttributes)->toBe([
            'color' => 'red',
            'size' => 'large',
        ])
        ->and($dto->sortOrder)->toBe($variant->sort_order)
        ->and($dto->status)->toBe($variant->status);
});
