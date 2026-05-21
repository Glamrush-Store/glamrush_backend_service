<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Product\DTOs\ProductCatalogDto;
use App\Domain\Catalog\Product\Mappers\ProductCatalogMapper;
use App\Shared\DTOs\PriceDto;
use Tests\Support\Factories\{BrandFactory, CategoryFactory, ProductFactory, ProductVariantFactory};

it('maps product model to product catalog dto correctly', function () {
    // Arrange
    $category = CategoryFactory::childOf(CategoryFactory::parent());

    $brand = BrandFactory::make();
    $variant = ProductVariantFactory::make();

    $product = ProductFactory::withRelations(
        ProductFactory::make(),
        $category,
        $brand
    );

    $product->setRelation('defaultVariant', $variant);

    // Act
    $dto = ProductCatalogMapper::fromModel($product);

    // Assert
    expect($dto)
        ->toBeInstanceOf(ProductCatalogDto::class)
        ->and($dto->id)->toBe($product->id)
        ->and($dto->name)->toBe($product->name)
        ->and($dto->slug)->toBe($product->slug)
        ->and($dto->category)->toBeInstanceOf(CategoryDto::class)
        ->and($dto->brand)->toBeInstanceOf(BrandDto::class)
        ->and($dto->shortDescription)->toBe($product->short_description)
        ->and($dto->description)->toBe($product->description)
        ->and($dto->metaTitle)->toBe($product->meta_title)
        ->and($dto->metaDescription)->toBe($product->meta_description)
        ->and($dto->isFeatured)->toBeTrue()
        ->and($dto->price)->toBeInstanceOf(PriceDto::class);
});
