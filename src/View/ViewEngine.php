<?php

namespace Whis\View;

interface ViewEngine
{
    public function render(
        string $view,
        array $parameters = [],
        ?string $layout = null,
        ?string $pageName = null
    ): string;
}