<?php

namespace App\Domain\Catalog\Brand\Entities;

final class BrandEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
    ) {
    }
}
