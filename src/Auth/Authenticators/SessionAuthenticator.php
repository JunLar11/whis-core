<?php

namespace Whis\Auth\Authenticators;

use Whis\Auth\Authenticatable;

class SessionAuthenticator implements Authenticator
{
    public function login(Authenticatable $subject){
        session()->set('_auth', $subject);
    }

    public function logout(Authenticatable $subject){
        session()->remove('_auth');
    }

    public function isAuthenticated(Authenticatable $subject):bool{
        return session()->get('_auth')?->id() === $subject->id();
    }

    public function resolve():?Authenticatable{
        //var_dump(session()->get('_auth'));
        return session()->get('_auth');
    }
}
