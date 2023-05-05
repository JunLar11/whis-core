<?php

namespace Whis\Providers;

use Whis\Cryptic\Bcryptic;
use Whis\Cryptic\Hasher;

class HasherServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match(config("hashing.hasher","bcryptic")) {
            "bcryptic" => singleton(Hasher::class,Bcryptic::class)
        };
    }

}
