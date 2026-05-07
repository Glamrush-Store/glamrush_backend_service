<?php

namespace App\Domain\Catalog\SavedItem\Entities;

final class SavedItemEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string $productId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $thumb,
    ) {}
}
