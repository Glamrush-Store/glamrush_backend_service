<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Catalog\Product\Views;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductCatalogView extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            //'short_description' => $this->shortDescription,
            //'description' => $this->description,
            'type' => $this->type,
            //'meta_title' => $this->metaTitle,
            //'meta_description' => $this->metaDescription,
            'is_featured' => $this->isFeatured,
            'price' => [
                'price' => $this->price->amount,
                'sale_price' => $this->price->saleAmount,
                'currency' => $this->price->currency,
                'on_sale' => $this->price->onSale
            ],
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug
            ],
            'brand' => [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug
            ],
        ];
    }
}
