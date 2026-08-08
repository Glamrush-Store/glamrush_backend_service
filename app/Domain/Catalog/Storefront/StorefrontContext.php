<?php

namespace App\Domain\Catalog\Storefront;

final class StorefrontContext
{
    private ?string $rootCategorySlug = null;

    /** @var list<string> */
    private array $categoryIds = [];

    /** @param list<string> $categoryIds */
    public function activate(string $rootCategorySlug, array $categoryIds): void
    {
        $this->rootCategorySlug = $rootCategorySlug;
        $this->categoryIds = array_values(array_unique($categoryIds));
    }

    public function isActive(): bool
    {
        return $this->rootCategorySlug !== null;
    }

    public function rootCategorySlug(): ?string
    {
        return $this->rootCategorySlug;
    }

    /** @return list<string> */
    public function categoryIds(): array
    {
        return $this->categoryIds;
    }

    public function rootCategoryId(): ?string
    {
        return $this->categoryIds[0] ?? null;
    }
}
