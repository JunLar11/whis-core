<?php

namespace Whis\Storage;

use Whis\App;

class FileManager
{

    protected string $storageDirectory;
    protected string $assetsDirectory;
    
    public function __construct(string $storageDirectory) {
        $this->storageDirectory = $storageDirectory;
        $this->assetsDirectory=App::$root."/resources/assets";
    }

    public function download(string $folder, string $filename) {
        $file_path = $this->assetsDirectory.'/'.$folder.'/'.$filename; // Set the file path (without extension)

        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Content-Length: ' . filesize($file_path));

            readfile($file_path);
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found.';
            exit;
        }
    }
    public function assets(string $folder, string $filename) {
        $file_path = $this->assetsDirectory.'/'.$folder.'/'.$filename; // Set the file path (without extension)

        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . filesize($file_path));

            readfile($file_path);
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found.';
            exit;
        }
    }
    public function uploaded(string $filename)
    {
        $file_path = $this->storageDirectory.'/'.$filename;
        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime_type);
            
            header('Content-Length: ' . filesize($file_path));

            readfile($file_path);
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found.';
            exit;
        }
    }

    public function downloadUploaded(string $filename)
    {
        $file_path = $this->storageDirectory.'/'.$filename;
        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Content-Length: ' . filesize($file_path));

            readfile($file_path);
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found.';
            exit;
        }
    }

}
