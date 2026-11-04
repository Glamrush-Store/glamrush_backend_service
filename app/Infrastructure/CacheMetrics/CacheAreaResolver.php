<?php

namespace App\Infrastructure\CacheMetrics;

final class CacheAreaResolver
{
    /** @return list<string> */
    public function areas(): array
    {
        return [
            'catalog',
            'content',
            'search',
            'settings',
            'dashboard',
            'homepage',
            'orders',
            'cart',
            'checkout',
            'payment',
            'shipping',
            'auth',
            'location',
            'other',
        ];
    }

    public function resolve(string $key): ?string
    {
        $normalized = strtolower($key);
        $metricsPrefix = strtolower((string) config('cache_metrics.key_prefix', 'glamrush:metrics:cache'));

        if (str_contains($normalized, $metricsPrefix)) {
            return null;
        }

        return match (true) {
            str_contains($normalized, 'catalog:'),
            str_contains($normalized, 'products'),
            str_contains($normalized, 'categories'),
            str_contains($normalized, 'brands'),
            str_contains($normalized, 'collections') => 'catalog',

            str_contains($normalized, 'content:'),
            str_contains($normalized, 'content_page'),
            str_contains($normalized, 'faq') => 'content',

            str_contains($normalized, 'search:'),
            str_contains($normalized, ':search:'),
            str_contains($normalized, 'search_') => 'search',

            str_contains($normalized, 'site-settings'),
            str_contains($normalized, 'runtime-settings'),
            str_contains($normalized, 'settings') => 'settings',

            str_contains($normalized, 'dashboard') => 'dashboard',
            str_contains($normalized, 'homepage'),
            str_contains($normalized, 'storefront_homepage') => 'homepage',
            str_contains($normalized, 'order') => 'orders',
            str_contains($normalized, 'cart') => 'cart',
            str_contains($normalized, 'checkout') => 'checkout',
            str_contains($normalized, 'payment') => 'payment',
            str_contains($normalized, 'shipping') => 'shipping',
            str_contains($normalized, 'location'),
            str_contains($normalized, 'reference:locations') => 'location',
            str_contains($normalized, 'password'),
            str_contains($normalized, 'auth'),
            str_contains($normalized, 'token') => 'auth',
            default => 'other',
        };
    }
}
