<?php

namespace Whis\View;

use Whis\App;
use Whis\Exceptions\HttpNotFoundException;

class StencilEngine implements ViewEngine
{
    protected array $blocks = array();

    protected string $viewsPath;
    protected string $compiledPath;
    protected string $layoutPath;
    protected string $defaultLayout="main";
    protected string $contentAnnotation="@content";
    protected string $urlAnnotation="@url";
    protected string $csrfAnnotation="@csrf";
    protected int $numero=0;
    protected string $extraDirectories="";

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = $viewsPath;
        $this->compiledPath = App::$root."/resources/views";
    }

    public function render($file, array $parameters =[], string $layout=null):string
    {
        $this->compile($file,$layout);

        return $this->renderView($file, $parameters);
    }

    protected function renderView(string $view, array $parameters=[]): string
    {
        return $this->phpFileOutput("{$this->compiledPath}/{$view}.php", $parameters);
    }

    protected function renderLayout(string $layout): string
    {
        //echo($this->viewsPath);
        return $this->phpFileOutput($this->layoutPath."/".($layout??$this->defaultLayout).".html");
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
    protected function compile($file,$layout=null)
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
        
        if (file_exists($compiled_file) && !str_contains(strtolower(config("app.env")),"dev")) {
            return;
        }elseif (!file_exists($compiled_file) && !str_contains(strtolower(config("app.env")),"dev")) {
            throw new HttpNotFoundException();
        }
        if(!file_exists("{$this->viewsPath}/{$file}.html")){
            header("HTTP/1.0 404 Not Found");
            echo "404 Not Found";
            exit;
        }
        $htmlFileTime= filemtime("{$this->viewsPath}/{$file}.html");
        if (!file_exists($compiled_file) || filemtime($compiled_file) < $htmlFileTime || filemtime($compiled_file) < filemtime($this->layoutPath."/".($layout??$this->defaultLayout).".html")) {
            $code = self::includeFiles("{$this->viewsPath}/{$file}.html",$layout);
            $code = self::compileCode($code);
            $this->deleteViewsNotUsed();
        
            file_put_contents($compiled_file, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);
        }
    }

    protected function compileCode($code)
    {
        //echo $code;
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);
        $code = $this->compilePHP($code);
        $code = $this->compileUrl($code);
        
        return $code;
    }
    protected function compileUrl($code)
    {
        return str_replace($this->urlAnnotation, config("app.url"), $code);
        // Fornece: Hll Wrld f PHP;
    }
    protected function includeFiles($file,bool|string $layout=null)
    {
        $code = file_get_contents($file);
        if(is_null($layout) || is_string($layout) && !is_bool($layout)){
            
            $layoutContent=$this->renderLayout($layout??$this->defaultLayout);
            $code=str_replace($this->contentAnnotation, $code, $layoutContent);          
        }
         
        $code=str_replace($this->csrfAnnotation, '<input type="hidden" name="_token" value="{{{$token}}}">', $code);
        preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            $includePath=str_replace($this->extraDirectories,"",$this->viewsPath);
            $include=$this->includeFiles($includePath.'/'. $value[2],false);
            //var_dump($include);
            //var_dump($include);
            $code = str_replace($value[0], $include, $code);
        }
        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
        return $code;
    }

    protected function compilePHP($code)
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    protected function compileEchos($code)
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    protected function compileEscapedEchos($code)
    {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo (!is_null($1) && $1!="")? htmlspecialchars($1, ENT_QUOTES, "UTF-8") :"" ?>', $code); 
    }

    protected function compileBlock($code)
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

    protected function compileYield($code)
    {
        foreach ($this->blocks as $block => $value) {
            $code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
        }
        $code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
        return $code;
    }

    protected function deleteViewsNotUsed()
    {
        $files = glob($this->compiledPath . '/**/*.php',GLOB_BRACE);
        $htmlFiles = glob($this->viewsPath . '/**/*.html');

        $html=[];
        foreach ($htmlFiles as $htmlFile) {
            $html[]=str_replace('.html', '', str_replace($this->viewsPath,'',$htmlFile));
        }
        
        $phpFiles=[];
        foreach ($files as $file) {
            $currentFile = $file;
            $phpFiles[]=str_replace('.php','',str_replace($this->compiledPath,'',$file));
        }
        // var_dump($html);
        // var_dump($phpFiles);
        // var_dump($files);
        $i=0;
        foreach ($phpFiles as $file) {
            if (!in_array($file,$html)) {
                unlink($files[$i]);
            }
        }
    }
    
}