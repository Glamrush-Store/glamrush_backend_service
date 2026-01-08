<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Application\UseCases\Catalog\GetProduct;

use App\Domain\Catalog\Repositories\ProductRepositoryInterface;
use RuntimeException;

final class GetProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products
    ) {}

    public function handle(GetProductQuery $query)
    {
        $product = $this->products->findById($query->productId);

        if (!$product) {
            throw new RuntimeException('Product not found');
        }

        return $product;
    }
}
