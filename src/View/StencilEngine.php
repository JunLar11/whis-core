<?php

namespace Whis\View;

class StencilEngine implements ViewEngine
{
    protected array $blocks = array();

    protected string $viewsPath;
    protected string $compiledPath;
    protected string $layoutPath;
    protected string $defaultLayout="main";
    protected string $contentAnnotation="@content";
    protected string $urlAnnotation="@url";

    protected string $extraDirectories="";

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = $viewsPath;
        $this->compiledPath = $viewsPath.'/compiled';
    }

    public function render($file, array $parameters =[], string $layout=null):string
    {
        $this->compile($file,$layout);

        return $this->renderView($file, $parameters);
    }

    protected function renderView(string $view, array $parameters=[]): string
    {
        return $this->compileCode($this->phpFileOutput("{$this->compiledPath}/{$view}.php", $parameters));
    }

    protected function renderLayout(string $layout): string
    {
        //echo($this->viewsPath);
        $path=str_replace($this->extraDirectories,"",$this->viewsPath);
        return $this->compileCode($this->phpFileOutput($this->layoutPath."/{$layout}.html"));
    }

    protected function layoutDirectoryPath(): void
    {
        $path=str_replace($this->extraDirectories,"",$this->viewsPath);
        $this->layoutPath="{$path}/layouts";
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
    public function compile($file,$layout=null)
    {
        $directories=[];
        $extra="";
        if(str_contains($file,"/")){
            $file.=".html";
            $directories=explode("/",$file);
            $i=0;
            while (!str_contains($directories[$i],".html")) {
                $extra.="/".$directories[$i];
                $i++;
                //echo $extra;
            }
            $file=str_replace(".html","",$directories[$i]);
        }
        if($extra!=""){
            $this->viewsPath.=$extra;
            $this->extraDirectories=$extra;
        }

        if (!file_exists($this->compiledPath.$extra)) {
            mkdir($this->compiledPath.$extra, 0744);
        }
        $compiled_file = $this->compiledPath.$extra."/{$file}.php";
        $this->layoutDirectoryPath();
        if (!file_exists($compiled_file) || filemtime($compiled_file) < filemtime("{$this->viewsPath}/{$file}.html") || filemtime($compiled_file) < filemtime($this->layoutPath."/".$layout??$this->defaultLayout.".html")) {
            $code = self::includeFiles("{$this->viewsPath}/{$file}.html",$layout);
            $code = self::compileCode($code);
            file_put_contents($compiled_file, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);
        }
    }

    public function compileCode($code)
    {
        
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);
        $code = $this->compilePHP($code);
        $code = $this->compileUrl($code);
        return $code;
    }
    public function compileUrl($code)
    {
        return str_replace($this->urlAnnotation, config("app.url"), $code);
        // Fornece: Hll Wrld f PHP;
    }
    public function includeFiles($file,$layout=null)
    {
        $layoutContent=$this->renderLayout($layout??$this->defaultLayout);
        $code = file_get_contents($file);
        $code=str_replace($this->contentAnnotation, $code, $layoutContent);
        preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            $code = str_replace($value[0], $this->includeFiles($this->viewsPath .'/'. $value[2]), $code);
        }
        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
        return $code;
    }

    public function compilePHP($code)
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    public function compileEchos($code)
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    public function compileEscapedEchos($code)
    {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
    }

    public function compileBlock($code)
    {
        preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            if (!array_key_exists($value[1], $this->blocks)) {
                $this->blocks[$value[1]] = '';
            }
            if (strpos($value[2], '@parent') === false) {
                $this->blocks[$value[1]] = $value[2];
            } else {
                $this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
            }
            $code = str_replace($value[0], '', $code);
        }
        return $code;
    }

    public function compileYield($code)
    {
        foreach ($this->blocks as $block => $value) {
            $code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
        }
        $code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
        return $code;
    }
}