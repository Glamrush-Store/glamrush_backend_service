<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Features\Brand\Views;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandView extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'metaTitle' => $this->metaTitle,
            'metaDescription' => $this->metaDescription,
            'sortOrder' => $this->sortOrder,
            'isActive' => $this->isActive,
        ];
    }
}
