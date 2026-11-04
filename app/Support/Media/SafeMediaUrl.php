<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final class SafeMediaUrl
{
    public static function get(Media $media, string $conversion = ''): string
    {
        try {
            return (string) $media->getUrl($conversion);
        } catch (Throwable $exception) {
            Log::warning('Unable to resolve media URL.', [
                'media_id' => (string) $media->getKey(),
                'disk' => (string) $media->disk,
                'conversion' => $conversion !== '' ? $conversion : 'original',
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return '';
        }
    }

    /** @return array{id: mixed, name: mixed, url: string, thumb: string, medium: string} */
    public static function image(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'url' => self::get($media),
            'thumb' => self::get($media, 'thumb'),
            'medium' => self::get($media, 'medium'),
        ];
    }
}
