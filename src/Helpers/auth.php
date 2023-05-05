<?php

use Whis\Auth\Auth;
use Whis\Auth\Authenticatable;

function auth():?Authenticatable{
    return Auth::user();
}
function isGuest():bool{
    return Auth::isGuest();
}
