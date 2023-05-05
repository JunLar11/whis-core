<?php

namespace Whis\View;

interface ViewEngine
{
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $parameters=[], string $layout=null): string;
}
