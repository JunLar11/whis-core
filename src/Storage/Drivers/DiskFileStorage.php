<?php

namespace Whis\Storage\Drivers;

use Whis\Storage\FileManager;

class DiskFileStorage implements FileStorageDriver
{
    protected string $storageDirectory;
    
    protected string $storageUri;

    protected string $appUrl;

    protected FileManager $fileManager;

    public function __construct(string $storageDirectory, string $storageUri, string $appUrl) {
        $this->storageDirectory = $storageDirectory;
        $this->storageUri = $storageUri;
        $this->appUrl = $appUrl;
        $this->fileManager = new FileManager($storageDirectory);
    }
    public function put(string $path, mixed $content): string {
        if (!is_dir($this->storageDirectory)) {
            mkdir($this->storageDirectory);
        }

        $directories = explode("/", $path);
        $file = array_pop($directories);
        $dir = "$this->storageDirectory/";

        if (count($directories) > 0) {
            $dir = $this->storageDirectory . "/" . implode("/", $directories);
            @mkdir($dir, recursive: true);
        }

        file_put_contents("$dir/$file", $content);

        return "$this->appUrl/$this->storageUri/$path";
    }

    public function download(string $folder, string $filename) {
        $this->fileManager->download($folder, $filename);
    }

    public function downloadUploaded(string $filename) {
        $this->fileManager->downloadUploaded($filename);
    }
    public function assets(string $folder, string $filename) {
        $this->fileManager->assets($folder, $filename);
    }

    public function uploaded(string $filename) {
        $this->fileManager->uploaded($filename);
    }

    public function remove(string $path): bool {
        if (file_exists($path)) {
            unlink($path);
            return true;
        } else {
            return false;
        }
    }
}
