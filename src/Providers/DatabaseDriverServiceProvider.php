<?php

namespace Whis\Providers;

use Whis\Database\Drivers\DatabaseDriver;
use Whis\Database\Drivers\PdoDriver;

class DatabaseDriverServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("database.connection", "mysql")) {
            "mysql","pgsql" => singleton(DatabaseDriver::class, PdoDriver::class),
        };
    }
}