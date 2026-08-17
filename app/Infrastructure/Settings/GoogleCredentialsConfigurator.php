<?php

namespace App\Infrastructure\Settings;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class GoogleCredentialsConfigurator
{
    private static ?string $configurationFingerprint = null;

    public function apply(): void
    {
        $encoded = config('filesystems.disks.gcs.key_file_base64');
        $useKeyFile = (bool) config('filesystems.disks.gcs.use_key_file', true);

        if (! $useKeyFile && is_string($encoded) && $encoded !== '') {
            $path = $this->materializeBase64Credentials($encoded);
            config()->set('filesystems.disks.gcs.key_file', $path);
            $this->setEnvironment($path);
        } elseif ($useKeyFile) {
            $path = config('filesystems.disks.gcs.key_file');

            if (is_string($path) && $path !== '') {
                $this->setEnvironment($path);
            }
        }

        $fingerprint = hash('sha256', serialize(config('filesystems.disks.gcs')));

        if (self::$configurationFingerprint !== null && ! hash_equals(self::$configurationFingerprint, $fingerprint)) {
            Storage::forgetDisk('gcs');
        }

        self::$configurationFingerprint = $fingerprint;
    }

    private function materializeBase64Credentials(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid GOOGLE_APPLICATION_CREDENTIALS_BASE64 value.');
        }

        $credentials = json_decode($decoded, true);

        if (! is_array($credentials) || ($credentials['type'] ?? null) !== 'service_account') {
            throw new RuntimeException('GOOGLE_APPLICATION_CREDENTIALS_BASE64 must contain a service-account JSON document.');
        }

        $directory = storage_path('app');
        $path = $directory.'/google-credentials.json';

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! file_exists($path) || ! hash_equals(hash('sha256', (string) file_get_contents($path)), hash('sha256', $decoded))) {
            file_put_contents($path, $decoded, LOCK_EX);
            @chmod($path, 0600);
        }

        return $path;
    }

    private function setEnvironment(string $path): void
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$path);
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = $path;
        $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] = $path;
    }
}
