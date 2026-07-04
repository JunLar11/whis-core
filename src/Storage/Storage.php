<?php
namespace Whis\Storage;

use Whis\Http\Response;
use Whis\Routing\Route;
use Whis\Storage\Drivers\FileStorageDriver;

class Storage
{
    /**
     * Put file in the storage directory.
     *
     * @param string $path
     * @param mixed $content
     * @return string URL of the file.
     */
    public static function put(
        string $path,
        mixed $content,
        bool $returnPath = false,
        ?string $alternativeDirectory = null,
        ?string $customUrl = null
    ): string {
        return app(FileStorageDriver::class)->put(
            $path,
            $content,
            $returnPath,
            $alternativeDirectory,
            $customUrl
        );
    }

    public static function remove(string $path): bool
    {
        return app(FileStorageDriver::class)->remove($path);
    }

    public static function download(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null,
        string $folder = ""
    ) {
        $filename = trim($folder . "/" . $filename, "/");

        if (in_array($folder, config("storage.assets")) || $asset) {
            return app(FileStorageDriver::class)->downloadFile(
                $filename,
                true,
                $alternativeDirectory
            );
        }

        if ($folder === "storage") {
            $filename = str_replace("storage/", "", $filename);
        }

        return app(FileStorageDriver::class)->downloadFile(
            $filename,
            false,
            $alternativeDirectory
        );
    }

    public static function get(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null,
        string $folder = ""
    ) {
        $filename = trim($folder . "/" . $filename, "/");

        if ($asset) {
            return app(FileStorageDriver::class)->getFile(
                $filename,
                true,
                $alternativeDirectory
            );
        }

        if ($folder === "storage") {
            $filename = str_replace("storage/", "", $filename);
        }

        return app(FileStorageDriver::class)->getFile(
            $filename,
            false,
            $alternativeDirectory
        );
    }

    public static function Routes()
    {
        /**
         * Rutas SEO directas
         */
        Route::get('/robots.txt', function () {
            return self::get('robots.txt', true);
        });

        Route::get('/sitemap.xml', function () {
            return self::get('sitemap.xml', true);
        });

        /**
         * Ruta específica para archivos privados/semipúblicos guardados en:
         *
         * storage/uploads/...
         *
         * Ejemplo:
         * /storage/uploads/profile_pictures/archivo.png
         */
        Route::get('/storage/uploads/{filename:.*}', function (string $filename) {
            if ($filename === '') {
                return;
            }

            return self::get(
                $filename,
                false,
                "storage/uploads"
            );
        });

        Route::get('/download/storage/uploads/{filename:.*}', function (string $filename) {
            if ($filename === '') {
                return;
            }

            return self::download(
                $filename,
                false,
                "storage/uploads"
            );
        });

        /**
         * Ruta genérica de descarga.
         */
        Route::get('/download/{folder}/{filename:.*}', function (string $folder, string $filename) {
            if ($folder === '' || $filename === '') {
                return;
            }

            $asset = in_array($folder, config("storage.assets"));

            return self::download(
                $filename,
                $asset,
                null,
                $folder
            );
        });

        /**
         * Ruta genérica de archivos.
         */
        Route::get('/{folder}/{filename:.*}', function (string $folder, string $filename) {
            if ($folder === '' || $filename === '') {
                return;
            }

            $asset = in_array($folder, config("storage.assets"));

            return self::get(
                $filename,
                $asset,
                null,
                $folder
            );
        });
    }
    public static function response(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null,
        bool $download = false,
        ?string $downloadName = null,
        bool $cache = false
    ): Response {
        $path = self::path($filename, $asset, $alternativeDirectory);

        if ($path === null) {
            return Response::text('404 Not Found')->setStatus(404);
        }

        return Response::file(
            $path,
            $download,
            $downloadName,
            $cache
        );
    }

    public static function path(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null
    ): ?string {
        $filename = trim(str_replace('\\', '/', $filename), '/');

        if ($filename === '' || str_contains($filename, '..') || str_contains($filename, "\0")) {
            return null;
        }

        if ($alternativeDirectory !== null) {
            $baseDirectory =
            rtrim(str_replace('\\', '/', App::$root), '/')
            . '/'
            . trim(str_replace('\\', '/', $alternativeDirectory), '/');
        } elseif ($asset) {
            $baseDirectory =
                rtrim(str_replace('\\', '/', App::$root), '/')
                . '/resources/assets';
        } else {
            $baseDirectory =
                rtrim(str_replace('\\', '/', App::$root), '/')
                . '/storage';
        }

        $baseReal = realpath($baseDirectory);

        if ($baseReal === false) {
            return null;
        }

        $candidate = rtrim(str_replace('\\', '/', $baseReal), '/') . '/' . $filename;
        $fileReal  = realpath($candidate);

        if ($fileReal === false) {
            return null;
        }

        $baseReal = rtrim(str_replace('\\', '/', $baseReal), '/');
        $fileReal = str_replace('\\', '/', $fileReal);

        if ($fileReal !== $baseReal && ! str_starts_with($fileReal, $baseReal . '/')) {
            return null;
        }

        if (! is_file($fileReal) || ! is_readable($fileReal)) {
            return null;
        }

        return $fileReal;
    }
}
