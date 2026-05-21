<?php

use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Product\DTOs\ProductDetailDto;
use App\Domain\Catalog\Product\Mappers\ProductDetailsMapper;
use App\Shared\DTOs\PriceDto;
use Tests\Support\Factories\{BrandFactory, CategoryFactory, ProductFactory, ProductVariantFactory};


it('maps product model to product details dto correctly', function () {
    // Arrange
    $category = CategoryFactory::childOf(CategoryFactory::parent());
    $brand = BrandFactory::make();

    $variants = collect([
        ProductVariantFactory::make(),
        ProductVariantFactory::make(['is_default' => false]),
    ]);

    $product = ProductFactory::withRelations(
        ProductFactory::make(),
        $category,
        $brand,
        $variants
    );

    // Act
    $dto = ProductDetailsMapper::fromModel($product);

    // Assert
    expect($dto)
        ->toBeInstanceOf(ProductDetailDto::class)
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
        ->and($dto->variants)->toHaveCount(2)
        ->and($dto->price)->toBeInstanceOf(PriceDto::class);
});
