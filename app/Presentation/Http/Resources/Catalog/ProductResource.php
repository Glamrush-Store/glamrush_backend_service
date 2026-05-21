<?php

namespace App\Presentation\Http\Resources\Catalog;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = new DateTimeImmutable();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'type' => $this->type,
            'isOnSale' => $this->isOnSale($now),

            // 'price' => $this->effectivePrice($now),
            'price' => $this->price,
            'stock_quantity' => $this->stockQuantity,
            'available' => $this->isAvailable(),

            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'images' => $this->images,

            'category' => $this->category
                ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ]
                : null,

            'brand' => $this->brand
                ? [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                ]
                : null,

            'default_attributes' => $this->displayDefaultAttributes(),

            'variants' => $this->displayVariants()
                ? array_map(function ($variant) use ($now) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'images' => $variant->images,
                        'isDefault' => $variant->isDefault(),
                        'price' => $variant->price,
                        'salePrice' => $variant->salePrice,
                        'stock_quantity' => $variant->stockQuantity,
                        'inStock' => $variant->isAvailable(),
                        'isOnSale' => $variant->isOnSale($now),
                        'available' => $variant->isAvailable(),
                        'attributes' => $variant->attributes(),
                    ];
                }, $this->displayVariants())
                : [],
        ];
    }
}
