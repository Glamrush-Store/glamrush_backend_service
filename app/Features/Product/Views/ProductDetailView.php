<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Features\Product\Views;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailView extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'metaTitle' => $this->metaTitle,
            'metaDescription' => $this->metaDescription,
            'isFeatured' => $this->isFeatured,
            'price' => [
                'price' => $this->price->amount,
                'salePrice' => $this->price->saleAmount,
                'currency' => $this->price->currency,
                'onSale' => $this->price->onSale
            ],
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'isActive' => $this->category->isActive
            ],
            'brand' => [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug
            ],
            'variants' => $this->variants

        ];
    }
}
