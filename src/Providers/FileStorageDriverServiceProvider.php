<?php

namespace Whis\Providers;

use Whis\App;
use Whis\Storage\Drivers\DiskFileStorage;
use Whis\Storage\Drivers\FileStorageDriver;

class FileStorageDriverServiceProvider implements ServiceProvider
{
    public function registerServices() {
        match (config("storage.driver", "disk")) {
            "disk" => singleton(
                FileStorageDriver::class,
                fn () => new DiskFileStorage(
                    App::$root . "/storage",
                    "storage",
                    config("app.url")
                )
            ),
        };
    }
}
