<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\FileSystem;

use Illuminate\Filesystem\FilesystemAdapter;

class GcsFilesystemAdapter extends FilesystemAdapter
{
    protected $config;

    public function __construct($filesystem, $adapter, array $config)
    {
        parent::__construct($filesystem, $adapter, $config);
        $this->config = $config;
    }

    public function url($path)
    {
        $baseUrl = rtrim($this->config['url'], '/');

        return $baseUrl . '/' . ltrim($path, '/');
    }
}
