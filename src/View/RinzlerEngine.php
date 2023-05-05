<?php

namespace Whis\View;

class RinzlerEngine implements ViewEngine
{
    protected string $viewsPath;

    protected string $defaultLayout="main";

    protected string $contentAnnotation="@content";

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = $viewsPath;
    }

    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $parameters =[], string $layout=null): string
    {
        $layoutContent=$this->renderLayout($layout??$this->defaultLayout);

        $viewContent=$this->renderView($view, $parameters);

        return str_replace($this->contentAnnotation, $viewContent, $layoutContent);
    }

    protected function renderView(string $view, array $parameters=[]): string
    {
        return $this->phpFileOutput("{$this->viewsPath}/{$view}.php", $parameters);
    }

    protected function renderLayout(string $layout): string
    {
        return $this->phpFileOutput("{$this->viewsPath}/layouts/{$layout}.php");
    }

    protected function phpFileOutput(string $phpFile, array $parameters=[]): string
    {
        foreach ($parameters as $paramerter=>$value) {
            $$paramerter=$value;
        }

        ob_start();
        include_once $phpFile;
        return ob_get_clean();
    }
}
