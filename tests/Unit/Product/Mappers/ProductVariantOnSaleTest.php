<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

use App\Domain\Catalog\Product\Mappers\ProductVariantMapper;
use App\Shared\DTOs\PriceDto;
use Tests\Support\Factories\ProductVariantFactory;

it('maps sale price when variant is on sale', function () {
    $variant = ProductVariantFactory::make([
        'price' => 4000,
        'sale_price' => 3500,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
        //'sale_starts_at' => '2026-01-01 00:00:00',
        //'sale_ends_at' => '2026-01-03 00:00:00',
    ]);


    $dto = ProductVariantMapper::fromModel($variant);

    expect($dto->price->onSale)->toBeTrue()
        ->and($dto->salePrice)->toBeInstanceOf(PriceDto::class)
        ->and($dto->salePrice->amount)->toBe(3500);
});
