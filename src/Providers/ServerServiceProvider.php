<?php

namespace Whis\Providers;

use Whis\Server\PhpNativeServer;
use Whis\Server\Server;

class ServerServiceProvider implements ServiceProvider {
    public function registerServices() {
        singleton(Server::class, PhpNativeServer::class);
    }
}