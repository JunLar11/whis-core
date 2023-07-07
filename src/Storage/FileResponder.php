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
            header("Cache-Control: private, max-age=86400, stale-while-revalidate=604800");
            header("Keep-Alive: timeout=5, max=100");
            header_remove("Pragma");
            
            $file_size = filesize($file_path);
            if(str_contains($mime_type,"video")){
                $this->sendVideo($file_path,$file_size);
            }
            header('Content-Length: ' . $file_size);


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
            header("Keep-Alive: timeout=5, max=100");
            $file_size=filesize($file_path);
            if(str_contains($mime_type,"video")){
                $this->sendVideo($file_path,$file_size);
            }
            header('Content-Length: ' . $file_size);
            header("Cache-Control: private, max-age=86400, stale-while-revalidate=604800");
            
            header_remove("Pragma");

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
            header("Keep-Alive: timeout=5, max=100");
            $file_size = filesize($file_path);
            header('Content-Length: ' . $file_size);

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
            header("Keep-Alive: timeout=5, max=100");
            $file_size = filesize($file_path);
            header('Content-Length: ' . $file_size);

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

    private function sendVideo(string $file_path, int $file_size){
        $fp = @fopen($file_path, 'rb');
        $size   = $file_size; // File size
        $length = $size;           // Content length
        $start  = 0;               // Start byte
        $end    = $size - 1;       // End byte
        header("Accept-Ranges: 0-$length");
        
        if (isset($_SERVER['HTTP_RANGE'])) {

            $c_start = $start;
            $c_end   = $end;
        
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            }else{
                $range  = explode('-', $range);
                $c_start = $range[0];
                $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: ".$length);
        
        
        $buffer = 1024 * 8;
        while(!feof($fp) && ($p = ftell($fp)) <= $end) {
        
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }
        
        fclose($fp);
        exit;
    }

}
