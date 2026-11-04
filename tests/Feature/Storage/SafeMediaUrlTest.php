<?php

use App\Support\Media\SafeMediaUrl;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('returns empty image URL fields when storage URL generation fails', function () {
    Log::spy();

    $media = new class extends Media
    {
        public function getUrl(string $conversionName = ''): string
        {
            throw new RuntimeException('Storage is unavailable.');
        }
    };

    $media->forceFill([
        'id' => 'media-1',
        'name' => 'Broken catalog image',
        'disk' => 'r2',
    ]);

    expect(SafeMediaUrl::image($media))->toMatchArray([
        'url' => '',
        'thumb' => '',
        'medium' => '',
    ]);

    Log::shouldHaveReceived('warning')->times(3);
});
