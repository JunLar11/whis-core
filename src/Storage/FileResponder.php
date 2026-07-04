<?php

namespace Whis\Storage;

use Whis\App;

class FileResponder
{
    protected string $storageDirectory;
    protected string $assetsDirectory;

    public function __construct(string $storageDirectory)
    {
        $this->storageDirectory =
            rtrim(str_replace('\\', '/', $storageDirectory), '/')
            . '/';

        $this->assetsDirectory =
            rtrim(str_replace('\\', '/', App::$root), '/')
            . '/resources/assets/';
    }

    public function getFile(?string $filename = null, bool $asset = false, ?string $alternativeDirectory = null): void
    {
        if ($asset) {
            $this->assets($filename, $alternativeDirectory);
            return;
        }

        $this->uploaded($filename, $alternativeDirectory);
    }

    public function downloadFile(?string $filename = null, bool $asset = false, ?string $alternativeDirectory = null): void
    {
        if ($asset) {
            $this->download($filename, $alternativeDirectory);
            return;
        }

        $this->downloadUploaded($filename, $alternativeDirectory);
    }

    public function assets(?string $filename = null, ?string $alternativeDirectory = null): void
    {
        $filePath = $this->buildPath($filename, true, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->serveFile($filePath, true);
    }

    public function uploaded(?string $filename = null, ?string $alternativeDirectory = null): void
    {
        $filePath = $this->buildPath($filename, false, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->serveFile($filePath, false);
    }

    public function download(?string $filename = null, ?string $alternativeDirectory = null): void
    {
        $filePath = $this->buildPath($filename, true, $alternativeDirectory);

        if (!$this->isValidFile($filePath)) {
            $this->notFound();
        }

        $this->forceDownload($filePath);
    }

    public function downloadUploaded(?string $filename = null, ?string $alternativeDirectory = null): void
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

        $filename = trim(str_replace('\\', '/', (string) $filename), '/');

        $baseDirectory = $asset
            ? $this->assetsDirectory
            : $this->storageDirectory;

        if ($alternativeDirectory !== null) {
            $baseDirectory =
                rtrim(str_replace('\\', '/', App::$root), '/')
                . '/'
                . trim(str_replace('\\', '/', $alternativeDirectory), '/');
        }

        $baseDirectory = realpath($baseDirectory);

        if ($baseDirectory === false) {
            return null;
        }

        $candidatePath =
            rtrim(str_replace('\\', '/', $baseDirectory), '/')
            . '/'
            . $filename;

        $filePath = realpath($candidatePath);

        if ($filePath === false) {
            return null;
        }

        $baseDirectory = rtrim(str_replace('\\', '/', $baseDirectory), '/');
        $filePath = str_replace('\\', '/', $filePath);

        if ($filePath !== $baseDirectory && !str_starts_with($filePath, $baseDirectory . '/')) {
            return null;
        }

        return $filePath;
    }

    private function isSafeFilename(?string $filename): bool
    {
        if ($filename === null) {
            return false;
        }

        $filename = trim(str_replace('\\', '/', $filename), '/');

        if ($filename === '') {
            return false;
        }

        if (str_contains($filename, "\0")) {
            return false;
        }

        if (str_contains($filename, '..')) {
            return false;
        }

        return true;
    }

    private function isValidFile(?string $filePath): bool
    {
        return $filePath !== null
            && file_exists($filePath)
            && is_file($filePath)
            && is_readable($filePath);
    }

    private function serveFile(string $filePath, bool $cache = true): void
    {
        $mimeType = $this->getContentType($filePath);
        $fileSize = filesize($filePath);

        if ($fileSize === false) {
            $this->notFound();
        }

        $this->cleanOutputBuffer();

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Keep-Alive: timeout=5, max=100');
        header_remove('Pragma');

        if ($cache) {
            header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
        } else {
            header('Cache-Control: private, no-store, max-age=0');
        }

        if (str_starts_with($mimeType, 'video/')) {
            $this->sendVideo($filePath, $fileSize, $mimeType);
            return;
        }

        readfile($filePath);
        exit;
    }

    private function forceDownload(string $filePath): void
    {
        $mimeType = $this->getContentType($filePath);
        $fileSize = filesize($filePath);

        if ($fileSize === false) {
            $this->notFound();
        }

        $this->cleanOutputBuffer();

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes(basename($filePath)) . '"');
        header('Content-Length: ' . $fileSize);
        header('Keep-Alive: timeout=5, max=100');
        header('Cache-Control: private, no-store, max-age=0');

        readfile($filePath);
        exit;
    }

    private function getContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'text/javascript; charset=UTF-8',
            'mjs'   => 'text/javascript; charset=UTF-8',
            'html'  => 'text/html; charset=UTF-8',
            'xml'   => 'application/xml; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'txt'   => 'text/plain; charset=UTF-8',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'avif'  => 'image/avif',
            'png'   => 'image/png',
            'jpg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',
            'pdf'   => 'application/pdf',
            'mp4'   => 'video/mp4',
            'webm'  => 'video/webm',
            'ogg'   => 'video/ogg',
            default => $this->detectMimeType($filePath),
        };
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        return $mimeType ?: 'application/octet-stream';
    }

    private function sendVideo(string $filePath, int $fileSize, string $mimeType): void
    {
        $fp = @fopen($filePath, 'rb');

        if ($fp === false) {
            $this->notFound();
        }

        $start = 0;
        $end = $fileSize - 1;
        $length = $fileSize;

        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE'])) {
            $rangeHeader = trim($_SERVER['HTTP_RANGE']);

            if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
                $this->rangeNotSatisfiable($fileSize);
            }

            $rangeStart = $matches[1];
            $rangeEnd = $matches[2];

            if ($rangeStart === '' && $rangeEnd === '') {
                $this->rangeNotSatisfiable($fileSize);
            }

            if ($rangeStart === '') {
                $suffixLength = (int) $rangeEnd;

                if ($suffixLength <= 0) {
                    $this->rangeNotSatisfiable($fileSize);
                }

                $start = max(0, $fileSize - $suffixLength);
            } else {
                $start = (int) $rangeStart;
            }

            if ($rangeEnd !== '' && $rangeStart !== '') {
                $end = min((int) $rangeEnd, $fileSize - 1);
            }

            if ($start > $end || $start >= $fileSize) {
                $this->rangeNotSatisfiable($fileSize);
            }

            $length = $end - $start + 1;

            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        }

        header('Content-Length: ' . $length);

        fseek($fp, $start);

        $buffer = 1024 * 8;
        $bytesLeft = $length;

        while (!feof($fp) && $bytesLeft > 0) {
            $readLength = min($buffer, $bytesLeft);
            $data = fread($fp, $readLength);

            if ($data === false) {
                break;
            }

            echo $data;
            flush();

            $bytesLeft -= strlen($data);
            set_time_limit(0);
        }

        fclose($fp);
        exit;
    }

    private function rangeNotSatisfiable(int $fileSize): void
    {
        http_response_code(416);
        header("Content-Range: bytes */{$fileSize}");
        exit;
    }

    private function cleanOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        exit('404 Not Found');
    }
}