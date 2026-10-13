<?php

namespace App\Infrastructure\Persistence\Eloquent\Services;

use App\Domain\Location\Services\LocationService;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ShippingZoneMatcher
{
    public function __construct(private readonly LocationService $locations) {}

    public function find(ShippingAddressEntity $address): ?ShippingZone
    {
        $countries = $this->normalized($this->locations->countryIdentifiers($address->country));
        $states = $this->normalized($this->locations->stateIdentifiers($address->country, $address->state));
        $city = $this->normalize($address->city);
        $hasLocationsTable = Schema::hasTable('shipping_zone_locations');

        $zones = ShippingZone::query()
            ->where('is_active', true)
            ->whereIn(DB::raw('LOWER(country)'), $countries)
            ->when($hasLocationsTable, fn ($query) => $query->with('locations'))
            ->orderBy('id')
            ->get();

        $bestZone = null;
        $bestScore = PHP_INT_MAX;

        foreach ($zones as $zone) {
            $score = $hasLocationsTable
                ? $this->locationScore($zone, $countries, $states, $city)
                : null;
            $score ??= $this->legacyScore($zone, $states, $city);

            if ($score !== null && $score < $bestScore) {
                $bestZone = $zone;
                $bestScore = $score;
            }
        }

        return $bestZone;
    }

    private function locationScore(ShippingZone $zone, array $countries, array $states, ?string $city): ?int
    {
        $best = null;

        foreach ($zone->locations as $location) {
            if (! in_array($this->normalize($location->country), $countries, true)) {
                continue;
            }

            $score = match ($location->scope) {
                'city' => $city !== null
                    && in_array($this->normalize($location->state), $states, true)
                    && $this->normalize($location->city) === $city ? 1 : null,
                'state' => in_array($this->normalize($location->state), $states, true) ? 2 : null,
                'country' => 3,
                default => null,
            };

            if ($score !== null && ($best === null || $score < $best)) {
                $best = $score;
            }
        }

        return $best;
    }

    private function legacyScore(ShippingZone $zone, array $states, ?string $city): ?int
    {
        if ($city !== null
            && in_array($this->normalize($zone->state), $states, true)
            && $this->normalize($zone->city) === $city) {
            return 1;
        }

        if ($zone->city === null && in_array($this->normalize($zone->state), $states, true)) {
            return 2;
        }

        if ($zone->state === null && $zone->city === null) {
            return 3;
        }

        return null;
    }

    private function normalized(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($value) => $this->normalize($value),
            $values,
        ))));
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return mb_strtolower(trim((string) $value));
    }
}
