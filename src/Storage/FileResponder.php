<?php

namespace Whis\Storage;

use Whis\App;

class FileResponder
{
    protected string $storageDirectory;
    protected string $assetsDirectory;

    public function __construct(string $storageDirectory)
    {
        $this->storageDirectory = rtrim($storageDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->assetsDirectory = rtrim(App::$root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR;
    }

    public function getFile(string $filename = null, bool $asset = false, string $alternativeDirectory = null)
    {
        if (!is_null($alternativeDirectory)) {
            $directories = explode("/", $filename);
            $filename = array_pop($directories);
        }

        if ($asset) {
            $this->assets($filename, $alternativeDirectory);
        } else {
            $this->uploaded($filename, $alternativeDirectory);
        }
    }

    public function downloadFile(string $filename = null, bool $asset = false, string $alternativeDirectory = null)
    {
        if (!is_null($alternativeDirectory)) {
            $directories = explode("/", $filename);
            $filename = array_pop($directories);
        }

        if ($asset) {
            $this->download($filename, $alternativeDirectory);
        } else {
            $this->downloadUploaded($filename, $alternativeDirectory);
        }
    }

    public function assets(string $filename, string $alternativeDirectory = null)
    {
        $filePath = $this->buildPath($filename, true, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->serveFile($filePath, true);
    }

    public function uploaded(string $filename, string $alternativeDirectory = null)
    {
        $filePath = $this->buildPath($filename, false, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->serveFile($filePath, false);
    }

    public function download(string $filename, string $alternativeDirectory = null)
    {
        $filePath = $this->buildPath($filename, true, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->forceDownload($filePath);
    }

    public function downloadUploaded(string $filename, string $alternativeDirectory = null)
    {
        $filePath = $this->buildPath($filename, false, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->forceDownload($filePath);
    }

    private function buildPath(?string $filename, bool $asset = false, ?string $alternativeDirectory = null): ?string
    {
        if (!$this->isSafeFilename($filename)) {
            return null;
        }

        $baseDirectory = $asset
            ? (is_null($alternativeDirectory) ? $this->assetsDirectory : rtrim(App::$root . '/' . trim($alternativeDirectory, '/\\'), '/\\'))
            : (is_null($alternativeDirectory) ? $this->storageDirectory : rtrim(App::$root . '/' . trim($alternativeDirectory, '/\\'), '/\\'));

        return rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR . ltrim($filename, '/\\');
    }

    private function isSafeFilename(?string $filename): bool
    {
        if (is_null($filename)) {
            return false;
        }

        $filename = trim($filename);

        if ($filename === '' || $filename === '/' || $filename === '\\') {
            return false;
        }

        if (str_contains($filename, '..')) {
            return false;
        }

        return true;
    }

    private function isValidFile(?string $filePath): bool
    {
        if (is_null($filePath)) {
            return false;
        }

        if (!file_exists($filePath)) {
            return false;
        }

        if (!is_file($filePath)) {
            return false;
        }

        if (!is_readable($filePath)) {
            return false;
        }

        return true;
    }

    private function serveFile(string $filePath, bool $cache = true): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $this->getContentType($filePath, $finfo);
        finfo_close($finfo);

        header('Content-Type: ' . $mimeType);
        header("Keep-Alive: timeout=5, max=100");
        header_remove("Pragma");

        if ($cache) {
            header("Cache-Control: private, max-age=86400, stale-while-revalidate=604800");
        }

        $fileSize = filesize($filePath);

        if (str_contains($mimeType, "video")) {
            $this->sendVideo($filePath, $fileSize);
        }

        header('Content-Length: ' . $fileSize);
        readfile($filePath);
        exit;
    }

    private function forceDownload(string $filePath): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $this->getContentType($filePath, $finfo);
        finfo_close($finfo);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header("Keep-Alive: timeout=5, max=100");
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        exit('404 Not Found');
    }

    private function getContentType($filePath, $finfo)
    {
        switch (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            case "css":
                return "text/css";
            case "js":
                return "text/javascript";
            case "html":
                return "text/html; charset=UTF-8";
            case "xml":
                return "application/xml; charset=UTF-8";
            case "txt":
                return "text/plain; charset=UTF-8";
            default:
                return finfo_file($finfo, $filePath);
        }
    }

    private function sendVideo(string $filePath, int $fileSize): void
    {
        $fp = @fopen($filePath, 'rb');

        if ($fp === false) {
            $this->notFound();
        }

        $size = $fileSize;
        $length = $size;
        $start = 0;
        $end = $size - 1;

        header("Accept-Ranges: 0-$length");

        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;

            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }

            $c_end = ($c_end > $end) ? $end : $c_end;

            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;

            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }

        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

        $buffer = 1024 * 8;

        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
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