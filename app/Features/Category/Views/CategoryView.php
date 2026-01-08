<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Features\Category\Views;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryView extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent' => $this->parent?->name,
            'parentSlug' => $this->parent?->slug,
            'description' => $this->description,
            'metaTitle' => $this->metaTitle,
            'metaDescription' => $this->metaDescription,
            'sortOrder' => $this->sortOrder,
            'isActive' => $this->isActive,
        ];
    }
}
