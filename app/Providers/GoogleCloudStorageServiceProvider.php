<?php

/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Providers;

use App\Filesystem\GcsFilesystemAdapter;
use Exception;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;

class GoogleCloudStorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('gcs', function ($app, $config) {
            // Build storage client config
            $clientConfig = [
                'projectId' => $config['project_id'],
            ];

            // Support both key_file and key_file_contents
            if (isset($config['key_file'])) {
                $clientConfig['keyFilePath'] = $config['key_file'];
            } elseif (isset($config['key_file_contents'])) {
                $clientConfig['keyFile'] = json_decode($config['key_file_contents'], true);
            }

            // Add HTTP client options to handle SSL (for development only)
            if (app()->environment('local')) {
                $clientConfig['httpHandler'] = function ($request, $options = []) {
                    $client = new Client([
                        'verify' => false, // Disable SSL verification for local dev
                    ]);

                    return $client->send($request, $options);
                };
            }

            $storageClient = new StorageClient($clientConfig);
            $bucket = $storageClient->bucket($config['bucket']);

            // Verify bucket is accessible
            if (!$bucket->exists()) {
                throw new Exception("Bucket {$config['bucket']} does not exist or is not accessible");
            }

            $pathPrefix = $config['path_prefix'] ?? '';

            // Create adapter - pass bucket and prefix only
            $adapter = new GoogleCloudStorageAdapter($bucket, $pathPrefix);

            // Create filesystem with adapter
            $filesystem = new Filesystem($adapter, [
                'disable_asserts' => true,
            ]);

            // Set the URL configuration for public URLs
            $config['url'] = $config['url'] ?? "https://storage.googleapis.com/{$config['bucket']}";

            // Add throw option to ensure errors are visible
            $config['throw'] = $config['throw'] ?? false;

            // return new FilesystemAdapter($filesystem, $adapter, $config);
            return new GcsFilesystemAdapter(
                $filesystem,
                $adapter,
                $config
            );
        });
    }
}
