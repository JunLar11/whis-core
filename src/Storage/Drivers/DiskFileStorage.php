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

    public function __construct(string $storageDirectory, string $storageUri, string $appUrl) {
        $this->storageDirectory = $storageDirectory;
        $this->storageUri = $storageUri;
        $this->appUrl = $appUrl;
        $this->fileResponder = new FileResponder($storageDirectory);
    }
    public function put(string $path, mixed $content, string $alternativeDirectory=null, string $customUrl=null): string {
        if (!is_dir($this->storageDirectory)) {
            mkdir($this->storageDirectory);
        }

        $directories = explode("/", $path);
        $file = array_pop($directories);
        $dir = (is_null($alternativeDirectory))?"$this->storageDirectory/":App::$root."/".$alternativeDirectory."/";

        if (count($directories) > 0) {
            $dir = ((is_null($alternativeDirectory))?"$this->storageDirectory":App::$root."/".$alternativeDirectory) . "/" . implode("/", $directories);
            @mkdir($dir, recursive: true);
        }

        file_put_contents("$dir/$file", $content);

        return "$this->appUrl/".(is_null($customUrl)?(($alternativeDirectory??$this->storageUri)."/$path"):($customUrl."/$file"));
    }

    public function getFile(string $filename=null,bool $asset=false, string $alternativeDirectory=null)
    {
        $this->fileResponder->getFile($filename, $asset, $alternativeDirectory);
    }
    public function downloadFile(string $filename=null,bool $asset=false, string $alternativeDirectory=null)
    {
        $this->fileResponder->downloadFile($filename, $asset, $alternativeDirectory);
        
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
