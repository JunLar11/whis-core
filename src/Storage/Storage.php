<?php

namespace Whis\Storage;

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
    public static function put(string $path, mixed $content): string {
        return app(FileStorageDriver::class)->put($path, $content);
    }
    public static function remove(string $path): bool {
        return app(FileStorageDriver::class)->remove($path);
    }
    public static function download(string $folder, string $filename) {
        return app(FileStorageDriver::class)->download($folder, $filename);
    }
    public static function assets(string $folder, string $filename) {
        return app(FileStorageDriver::class)->assets($folder, $filename);
    }

    public static function downloadUploaded(string $filename) {
        return app(FileStorageDriver::class)->downloadUploaded($filename);
    }
    public static function uploaded(string $filename) {
        return app(FileStorageDriver::class)->uploaded($filename);
    }

    public static function Routes() {
        // Route::get('/{folder}/{filename:.*}', function (string $folder, string $filename) {
        //     self::assets($folder, $filename);
        // });

        /** 
         * Cambiar de orden las rutas para que no se sobrepongan con las rutas de la aplicación ni entre ellas.
        */
        Route::get('/download/storage/{filename:.*}', function (string $filename) {
            self::downloadUploaded($filename);
        });
        Route::get('/storage/{filename:.*}', function (string $filename) {
            self::uploaded($filename);
        });


        Route::get('/download/{folder}/{filename:.*}', function (string $folder, string $filename) {
            self::download($folder, $filename);
        });
        Route::get('/{folder}/{filename:.*}', function (string $folder, string $filename) {
            self::assets($folder, $filename);
        });
    }
}
