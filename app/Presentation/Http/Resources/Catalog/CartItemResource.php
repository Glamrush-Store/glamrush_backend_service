<?php

namespace App\Presentation\Http\Resources\Catalog;

use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CartItemResource extends JsonResource
{
    public function __construct(private readonly CartItemEntity $entity)
    {
        parent::__construct($entity);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->entity->id,
            'product_id' => $this->entity->productId,
            'name'       => $this->entity->name,
            'slug'       => $this->entity->slug,
            'thumb'      => $this->entity->thumb,
            'quantity'   => $this->entity->quantity,
            'expires_at' => $this->entity->expiresAt->toISOString(),
        ];
    }
}
