<?php

use Illuminate\Support\Facades\Storage;

it('builds public media URLs with the configured Cloudflare R2 domain', function () {
    config()->set('filesystems.disks.r2', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'auto',
        'bucket' => 'test-bucket',
        'url' => 'https://media.example.test',
        'endpoint' => 'https://account-id.r2.cloudflarestorage.com',
        'use_path_style_endpoint' => false,
        'throw' => true,
        'report' => true,
    ]);

    Storage::forgetDisk('r2');

    expect(Storage::disk('r2')->url('catalog/example.webp'))
        ->toBe('https://media.example.test/catalog/example.webp');
});
