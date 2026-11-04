<?php

namespace App\Infrastructure\Persistence\Eloquent\Enrichers;

use App\Infrastructure\Persistence\Eloquent\Models\AttributeType;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductAttribute;
use Illuminate\Support\Collection;

final class VariantAttributeEnricher
{
    public function enrich(Product $product): void
    {
        $this->enrichMany(collect([$product]));
    }

    public function enrichMany(Collection $products): void
    {
        $allTypes = collect();
        $allValues = collect();

        $products->each(function (Product $product) use (&$allTypes, &$allValues) {
            $product->variants->each(function ($variant) use (&$allTypes, &$allValues) {
                foreach ($variant->attributes ?? [] as $attr) {
                    if (!$this->isValid($attr)) {
                        continue;
                    }
                    $allTypes->push($attr['type']);
                    $allValues->push($attr['value']);
                }
            });
        });

        $uniqueTypes = $allTypes->unique()->values();
        $uniqueValues = $allValues->unique()->values();

        if ($uniqueTypes->isEmpty()) {
            return;
        }

        $productAttributes = ProductAttribute::whereIn('type', $uniqueTypes)
            ->whereIn('value', $uniqueValues)
            ->get()
            ->groupBy('type')
            ->map(fn(Collection $group) => $group->keyBy('value'));

        $attributeTypes = AttributeType::whereIn('value', $uniqueTypes)
            ->get()
            ->keyBy('value');

        $products->each(function (Product $product) use ($productAttributes, $attributeTypes) {
            $product->variants->each(function ($variant) use ($productAttributes, $attributeTypes) {
                $enriched = collect($variant->attributes ?? [])
                    ->filter(fn($attr) => $this->isValid($attr))
                    ->map(function (array $attr) use ($productAttributes, $attributeTypes) {
                        $type = $attr['type'];
                        $rawValue = $attr['value'];
                        $match = $productAttributes->get($type)?->get($rawValue);

                        return [
                            'type'         => $type,
                            'code'         => $match?->code,
                            'value'        => $match?->value ?? $rawValue,
                            'display_type' => $attributeTypes->get($type)?->display_type,
                            'meta'         => $match?->meta,
                        ];
                    })
                    ->values()
                    ->all();

                $variant->setAttribute('attributes', $enriched);
            });
        });
    }

    private function isValid(mixed $attr): bool
    {
        return isset($attr['type'], $attr['value'])
            && is_string($attr['type'])
            && is_string($attr['value']);
    }
}
