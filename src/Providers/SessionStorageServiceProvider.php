<?php

namespace Whis\Providers;

use Whis\Session\PhpNativeSessionStorage;
use Whis\Session\SessionStorage;

class SessionStorageServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("session.storage", "native")) {
            "native" => singleton(SessionStorage::class, PhpNativeSessionStorage::class),
        };
    }
}