<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\DTOs;


use App\Domain\Catalog\Brand\DTOs\BrandDto;
use App\Domain\Catalog\Category\DTOs\CategoryDto;
use App\Domain\Catalog\Product\Enums\ProductType;
use App\Shared\DTOs\PriceDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductCatalog',
    title: 'Product Catalog'
)]
final class ProductCatalogDto
{
    public function __construct(

        #[OA\Property(
            example: '01J2P9WQZ3YJ6R8X9A2C4D5E6F'
        )]
        public readonly string $id,

        #[OA\Property(
            example: 'Face Scrub'
        )]
        public readonly string $name,

        #[OA\Property(
            example: 'face-scrub'
        )]
        public readonly ?string $slug,

        #[OA\Property(ref: '#/components/schemas/Categories')]
        public readonly ?CategoryDto $category,

        #[OA\Property(ref: '#/components/schemas/Brands')]
        public readonly ?BrandDto $brand,


        #[OA\Property(
            example: 'Facial scrub lemon fragrance '
        )]
        public readonly ?string $shortDescription,

        #[OA\Property(
            example: 'Facial scrub lemon fragrance with very much more additional info'
        )]
        public readonly ?string $description,

        #[OA\Property(example: 'variant')]
        public readonly ProductType $type,

        #[OA\Property(example: 'Facial scrub lemon fragrance')]
        public readonly ?string $metaTitle,

        #[OA\Property(
            example: 'Facial scrub lemon fragrance with very much more additional info'
        )]
        public readonly ?string $metaDescription,

        #[OA\Property(example: 'true')]
        public readonly bool $isFeatured,

        #[OA\Property(ref: '#/components/schemas/Price')]
        public readonly ?PriceDto $price
    ) {
    }
}
