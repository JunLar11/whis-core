<?php

namespace Whis\Storage\Drivers;

use Whis\App;
use Whis\Storage\FileResponder;

class DiskFileStorage implements FileStorageDriver
{
    protected string $storageDirectory;
    protected string $storageUri;
    protected string $appUrl;
    protected FileResponder $fileResponder;

    public function __construct(string $storageDirectory, string $storageUri, string $appUrl)
    {
        $this->storageDirectory = rtrim(str_replace('\\', '/', $storageDirectory), '/');
        $this->storageUri       = trim(str_replace('\\', '/', $storageUri), '/');
        $this->appUrl           = rtrim($appUrl, '/');
        $this->fileResponder    = new FileResponder($storageDirectory);
    }

    public function put(
        string $path,
        mixed $content,
        bool $returnPath = false,
        ?string $alternativeDirectory = null,
        ?string $customUrl = null
    ): string {
        $path = trim(str_replace('\\', '/', $path), '/');

        $baseDir = $alternativeDirectory !== null
            ? App::$root . "/" . trim(str_replace('\\', '/', $alternativeDirectory), '/')
            : $this->storageDirectory;

        $fullPath = rtrim($baseDir, '/') . "/" . $path;
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($fullPath, $content);

        if ($returnPath) {
            return $fullPath;
        }

        /*
         * CLAVE:
         * Usa $path, no basename($path).
         *
         * Correcto:
         * /storage/uploads/profile_pictures/archivo.jpeg
         *
         * Incorrecto:
         * /storage/uploads/archivo.jpeg
         */
        if ($customUrl !== null) {
            $urlBase = trim(str_replace('\\', '/', $customUrl), '/');

            return $this->appUrl . "/" . $urlBase . "/" . $path;
        }

        $urlBase = $alternativeDirectory !== null
            ? trim(str_replace('\\', '/', $alternativeDirectory), '/')
            : $this->storageUri;

        return $this->appUrl . "/" . $urlBase . "/" . $path;
    }

    public function getFile(?string $filename = null, bool $asset = false, ?string $alternativeDirectory = null)
    {
        $this->fileResponder->getFile($filename, $asset, $alternativeDirectory);
    }

    public function downloadFile(?string $filename = null, bool $asset = false, ?string $alternativeDirectory = null)
    {
        $this->fileResponder->downloadFile($filename, $asset, $alternativeDirectory);
    }

    public function remove(string $path): bool
    {
        if (file_exists($path)) {
            unlink($path);
            return true;
        }

        return false;
    }
}