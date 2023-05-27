<?php

namespace Whis\Storage;

use Whis\App;

class FileResponder
{

    protected string $storageDirectory;
    protected string $assetsDirectory;
    
    public function __construct(string $storageDirectory) {
        $this->storageDirectory = $storageDirectory.'/';
        $this->assetsDirectory=App::$root."/resources/assets/";
    }

    

    public function getFile(string $filename=null,bool $asset=false, string $alternativeDirectory=null){
        if(!is_null($alternativeDirectory)){
            $directories = explode("/", $filename);
            $filename = array_pop($directories);
        }
        if($asset){
            
            $this->assets($filename,$alternativeDirectory);
        }else{
            $this->uploaded($filename,$alternativeDirectory);
        }
    }

    public function downloadFile(string $filename=null,bool $asset=false, string $alternativeDirectory=null){
        if(!is_null($alternativeDirectory)){
            $directories = explode("/", $filename);
            $filename = array_pop($directories);
        }
        if($asset){
            
            $this->download($filename,$alternativeDirectory);
        }else{
            $this->downloadUploaded($filename,$alternativeDirectory);
        }
    }

    public function assets(string $filename, string $alternativeDirectory=null) {
        $file_path = ((is_null($alternativeDirectory))?$this->assetsDirectory:App::$root.'/'.$alternativeDirectory).'/'.$filename; // Set the file path (without extension)

        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type=$this->getContentType($file_path, $finfo);
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
    public function uploaded(string $filename, string $alternativeDirectory=null)
    {
        $file_path = ((is_null($alternativeDirectory))?$this->storageDirectory:App::$root.'/'.$alternativeDirectory).'/'.$filename; // Set the file path (without extension)
        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            
            $mime_type=$this->getContentType($file_path, $finfo);

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

    public function download(string $filename, string $alternativeDirectory=null) {
        $file_path = ((is_null($alternativeDirectory))?$this->assetsDirectory:App::$root.'/'.$alternativeDirectory).'/'.$filename; // Set the file path (without extension)

        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type=$this->getContentType($file_path, $finfo);
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

    public function downloadUploaded(string $filename, string $alternativeDirectory=null)
    {
        $file_path = ((is_null($alternativeDirectory))?$this->storageDirectory:App::$root.'/'.$alternativeDirectory).'/'.$filename; // Set the file path (without extension)
        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type=$this->getContentType($file_path, $finfo);
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

    private function getContentType($file_path, $finfo)
    {
        switch(pathinfo($file_path, PATHINFO_EXTENSION)){
            case "css":
                return "text/css";
                break;
            case "js":
                return "text/javascript";
                break;
            case "html":
                return "text/html";
                break;
            default:
                return finfo_file($finfo, $file_path);
                break;
        }
    }

}
