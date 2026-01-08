<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\UseCases\Catalog\GetProduct\GetProductHandler;
use App\Application\UseCases\Catalog\GetProduct\GetProductQuery;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\Request;

final class ProductController
{
    public function show(
        string $id,
        GetProductHandler $handler
    ) {
        $product = $handler->handle(
            new GetProductQuery(($id))
        );

        return new ProductResource($product);
    }
}
