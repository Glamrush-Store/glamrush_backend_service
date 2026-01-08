<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

use App\Features\Brand\DTOs\BrandDto;
use App\Features\Brand\Mappers\BrandDtoMapper;
use Tests\Support\Factories\BrandFactory;

it('maps brand model to brand dto correctly', function () {
    // Arrange
    $brand = BrandFactory::make();


    // Act
    $dto = BrandDtoMapper::fromModel($brand);


    // Assertions
    expect($dto)->toBeInstanceOf(BrandDto::class)
        ->and($dto->id)->toBe($brand->id)
        ->and($dto->name)->toBe($brand->name)
        ->and($dto->slug)->toBe($brand->slug)
        ->and($dto->description)->toBe($brand->description)
        ->and($dto->metaTitle)->toBe($brand->meta_title)
        ->and($dto->metaDescription)->toBe($brand->meta_description)
        ->and($dto->sortOrder)->toBe($brand->sort_order)
        ->and($dto->isActive)->toBeTrue();
});

