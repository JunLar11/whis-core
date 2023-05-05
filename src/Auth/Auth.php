<?php

namespace Whis\Auth;

use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use Whis\Auth\Authenticators\Authenticator;
use Whis\Routing\Route;

class Auth
{
    public static function user():?Authenticatable{
        return app(Authenticator::class)->resolve();
    }

    public static function isGuest():bool{
        return is_null(self::user());
    }

    public static function Routes():void{
        Route::get('/register', [RegisterController::class,'create']);
        Route::get('/login', [LoginController::class,'create']);
        Route::post('/login', [LoginController::class,'store']);
        Route::post('/register', [RegisterController::class,'store']);
        Route::get('/logout', [LoginController::class,'destroy']);

    }
}
