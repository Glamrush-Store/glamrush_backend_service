<?php

namespace App\Infrastructure\Settings;

use Illuminate\Support\Facades\Storage;

final class CloudflareR2Configurator
{
    private static ?string $configurationFingerprint = null;

    public function apply(): void
    {
        $fingerprint = hash('sha256', serialize(config('filesystems.disks.r2')));

        if (
            self::$configurationFingerprint !== null
            && ! hash_equals(self::$configurationFingerprint, $fingerprint)
        ) {
            Storage::forgetDisk('r2');
        }

        self::$configurationFingerprint = $fingerprint;
    }
}
