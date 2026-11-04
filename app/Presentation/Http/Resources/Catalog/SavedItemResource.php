<?php

namespace App\Presentation\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->productId,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'thumb'      => $this->thumb,
        ];
    }
}
