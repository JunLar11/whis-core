<?php

namespace Whis\Auth\Authenticators;

use Whis\Auth\Authenticatable;

interface Authenticator
{
    public function login(Authenticatable $subject);
    public function logout(Authenticatable $subject);
    public function isAuthenticated(Authenticatable $subject):bool;
    public function resolve():?Authenticatable;
}
