<?php

use App\Features\Category\DTOs\CategoryDto;
use App\Features\Category\DTOs\CategoryParentDto;
use App\Features\Category\Mappers\CategoryDtoMapper;
use Tests\Support\Factories\CategoryFactory;

it('maps category model to category dto correctly', function () {
    // Arrange
    $parent = CategoryFactory::parent();

    $category = CategoryFactory::childOf($parent);

    // Act
    $dto = CategoryDtoMapper::fromModel($category);


    // Assertions
    expect($dto)->toBeInstanceOf(CategoryDto::class)
        ->and($dto->id)->toBe($category->id)
        ->and($dto->name)->toBe($category->name)
        ->and($dto->parent)->toBeInstanceOf(CategoryParentDto::class)
        ->and($dto->slug)->toBe($category->slug)
        ->and($dto->description)->toBe($category->description)
        ->and($dto->metaTitle)->toBe($category->meta_title)
        ->and($dto->metaDescription)->toBe($category->meta_description)
        ->and($dto->sortOrder)->toBe($category->sort_order)
        ->and($dto->isActive)->toBeTrue();
});
