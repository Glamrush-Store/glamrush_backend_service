<?php

namespace App\Presentation\Http\Resources\Content;

use App\Domain\Content\Enums\ContentPageType;
use App\Support\Media\SafeMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

final class PublicContentPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'navigation_title' => $this->navigation_title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'page_type' => $this->page_type->value,
            'settings' => $this->page_type === ContentPageType::Contact ? $this->publicContactSettings() : null,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => SafeMediaUrl::get($media),
            ])->values()->all()),
            'published_at' => $this->published_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function publicContactSettings(): array
    {
        $settings = Arr::only($this->settings ?? [], [
            'email', 'phone', 'whatsapp', 'business_hours', 'address', 'map_url', 'social_links',
        ]);

        $settings['map_url'] = $this->safeHttpsUrl($settings['map_url'] ?? null);
        $settings['social_links'] = collect($settings['social_links'] ?? [])
            ->filter(fn ($link) => is_array($link)
                && in_array($link['platform'] ?? null, ['instagram', 'facebook', 'x', 'tiktok', 'youtube', 'linkedin'], true)
                && $this->safeHttpsUrl($link['url'] ?? null) !== null)
            ->map(fn (array $link) => [
                'platform' => $link['platform'],
                'url' => $this->safeHttpsUrl($link['url']),
            ])
            ->values()
            ->all();

        return $settings;
    }

    private function safeHttpsUrl(mixed $value): ?string
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https' ? $value : null;
    }
}
