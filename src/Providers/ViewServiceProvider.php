<?php

namespace Whis\Providers;
use Whis\View\StencilEngine;
use Whis\View\ViewEngine;

class ViewServiceProvider implements ServiceProvider
{
    public function registerServices(){
        match(config("view.engine", "stencil")){
            "stencil" => singleton(ViewEngine::class, fn()=>new StencilEngine(config("view.path")))
        };
    }

}
