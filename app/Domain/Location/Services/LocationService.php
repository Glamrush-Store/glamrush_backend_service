<?php

namespace App\Domain\Location\Services;

use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;

final class LocationService
{
    /** @return array<int, array{label: string, code: string}> */
    public function countries(): array
    {
        return array_values(array_map(
            fn (array $country): array => [
                'label' => (string) $country['name'],
                'code' => (string) $country['code3'],
            ],
            $this->dataset(),
        ));
    }

    /** @return array<int, array{label: string, value: string}>|null */
    public function states(string $countryIdentifier): ?array
    {
        $country = $this->findCountry($countryIdentifier);

        if ($country === null) {
            return null;
        }

        return array_values(array_map(
            fn (array $state): array => [
                'label' => (string) $state['name'],
                'value' => trim((string) $state['code']),
            ],
            $country['states'] ?? [],
        ));
    }

    /** @return array<int, array{label: string, value: string}>|null */
    public function cities(string $countryIdentifier, string $stateIdentifier): ?array
    {
        $country = $this->findCountry($countryIdentifier);

        if ($country === null) {
            return null;
        }

        $state = $this->findState($country, $stateIdentifier);

        if ($state === null) {
            return null;
        }

        $subdivisions = is_array($state['subdivision'] ?? null) ? $state['subdivision'] : [];

        return array_values(array_map(
            fn (string $subdivision): array => [
                'label' => $subdivision,
                'value' => $subdivision,
            ],
            array_values(array_unique(array_filter($subdivisions, 'is_string'))),
        ));
    }

    /** @return string[] */
    public function countryIdentifiers(string $identifier): array
    {
        $country = $this->findCountry($identifier);

        if ($country === null) {
            return [trim($identifier)];
        }

        return array_values(array_unique(array_filter([
            trim($identifier),
            trim((string) ($country['code2'] ?? '')),
            trim((string) ($country['code3'] ?? '')),
            trim((string) ($country['name'] ?? '')),
        ])));
    }

    /** @return string[] */
    public function stateIdentifiers(string $countryIdentifier, ?string $stateIdentifier): array
    {
        if ($stateIdentifier === null || trim($stateIdentifier) === '') {
            return [];
        }

        $country = $this->findCountry($countryIdentifier);
        $state = $country ? $this->findState($country, $stateIdentifier) : null;

        if ($state === null) {
            return [trim($stateIdentifier)];
        }

        return array_values(array_unique(array_filter([
            trim($stateIdentifier),
            trim((string) ($state['code'] ?? '')),
            trim((string) ($state['name'] ?? '')),
        ])));
    }

    private function findCountry(string $identifier): ?array
    {
        $needle = trim($identifier);

        foreach ($this->dataset() as $country) {
            foreach (['code2', 'code3', 'name'] as $field) {
                if (isset($country[$field]) && strcasecmp(trim((string) $country[$field]), $needle) === 0) {
                    return $country;
                }
            }
        }

        return null;
    }

    private function findState(array $country, string $identifier): ?array
    {
        $needle = trim($identifier);

        foreach ($country['states'] ?? [] as $state) {
            foreach (['code', 'name'] as $field) {
                if (isset($state[$field]) && strcasecmp(trim((string) $state[$field]), $needle) === 0) {
                    return $state;
                }
            }
        }

        return null;
    }

    private function dataset(): array
    {
        $path = resource_path('files/countries.json');

        if (! is_file($path)) {
            throw new RuntimeException('The location dataset is unavailable.');
        }

        $version = sprintf('%s:%s', filemtime($path) ?: 0, filesize($path) ?: 0);

        return Cache::remember("reference:locations:{$version}", now()->addDay(), function () use ($path): array {
            try {
                $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('The location dataset is invalid.', previous: $exception);
            }

            if (! is_array($data)) {
                throw new RuntimeException('The location dataset is invalid.');
            }

            return $data;
        });
    }
}
