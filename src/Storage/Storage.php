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
    public static function put(string $path, mixed $content, bool $returnPath=false, string $alternativeDirectory=null, string $customUrl=null): string {
        return app(FileStorageDriver::class)->put($path, $content, $returnPath, $alternativeDirectory, $customUrl);
    }
    public static function remove(string $path): bool {
        return app(FileStorageDriver::class)->remove($path);
    }
    public static function download(string $filename, ?bool $asset=false, string $alternativeName=null ,string $folder="") {
        $filename=$folder."/".$filename;
        if(in_array($folder,config("storage.assets"))||$asset){
            return app(FileStorageDriver::class)->downloadFile($filename, true);
        }else{
            if($folder=="storage"){
                $filename=str_replace("storage/","",$filename);
            }
            return app(FileStorageDriver::class)->downloadFile($filename, false);
        }
    }
    public static function get( string $filename, ?bool $asset=false, string $alternativeFolder=null ,string $folder="") {

        $filename=$folder."/".$filename;
        if($asset){
            return app(FileStorageDriver::class)->getFile($filename, true, $alternativeFolder);
        }else{
            if($folder=="storage"){
                $filename=str_replace("storage/","",$filename);
            }
            return app(FileStorageDriver::class)->getFile($filename, false, $alternativeFolder);
        }
    }


    public static function Routes() {
        // Route::get('/{folder}/{filename:.*}', function (string $folder, string $filename) {
        //     self::assets($folder, $filename);
        // });

        /** 
         * Cambiar de orden las rutas para que no se sobrepongan con las rutas de la aplicación ni entre ellas.
        */


        Route::get('/download/{folder}/{filename:.*}', function (string $folder, string $filename) {
            $asset=(in_array($folder,config("storage.assets"))?true:false);
            self::download($filename, $asset, null, $folder);
        });
        Route::get('/{folder}/{filename:.*}', function (string $folder, string $filename) {
            $asset=(in_array($folder,config("storage.assets"))?true:false);
            self::get( $filename, $asset, null, $folder);
        });
    }
}
