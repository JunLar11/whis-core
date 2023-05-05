<?php

namespace Whis\Providers;

use Whis\Auth\Authenticators\Authenticator;
use Whis\Auth\Authenticators\SessionAuthenticator;

class AuthenticationServiceProvider implements ServiceProvider
{
    public function registerServices(){
        match(config("auth.method","session")){
            "session"=>singleton(Authenticator::class,SessionAuthenticator::class)
        };
    }
}
