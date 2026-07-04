<?php
namespace Whis\View;

use Whis\App;
use Whis\Exceptions\HttpNotFoundException;

class StencilEngine implements ViewEngine
{
    protected array $blocks       = [];
    protected array $dependencies = [];

    /**
     * Raíz absoluta de vistas.
     *
     * Debe apuntar a:
     * assets/views
     *
     * Ejemplo:
     * App::$root . "/assets/views"
     */
    protected string $viewsPath;

    /**
     * Carpeta donde se guardan las vistas PHP compiladas.
     *
     * Ejemplo:
     * resources/views
     */
    protected string $compiledPath;

    /**
     * Layout default.
     *
     * Ahora es ruta relativa desde assets/views.
     *
     * Por defecto:
     * assets/views/layouts/main.html
     *
     * Puedes cambiarlo a:
     * protected string $defaultLayout = "plantillas/base";
     *
     * Y buscaría:
     * assets/views/plantillas/base.html
     */
    protected string $defaultLayout = 'layouts/main';

    protected string $contentAnnotation  = '@content';
    protected string $urlAnnotation      = '@url';
    protected string $csrfAnnotation     = '@csrf';
    protected string $appNameAnnotation  = '@AppName';
    protected string $pageNameAnnotation = '@pageName';

    public function __construct(string $viewsPath)
    {
        $this->viewsPath    = $this->normalizeRootPath($viewsPath);
        $this->compiledPath = $this->normalizeRootPath(App::$root . '/resources/views');
    }

    public function render(
        string $file,
        array $parameters = [],
        ?string $layout = null,
        ?string $pageName = null
    ): string {
        /*
         * @pageName NO se compila como texto fijo.
         * Se inyecta como variable runtime en el archivo compilado.
         */
        $parameters['pageName'] = $pageName ?? ($parameters['pageName'] ?? '');

        $view = $this->normalizeViewName($file);

        $this->compile($view, $layout);

        return $this->renderView($view, $parameters);
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->phpFileOutput($this->compiledFilePath($view), $parameters);
    }

    protected function phpFileOutput(string $phpFile, array $parameters = []): string
    {
        foreach ($parameters as $parameter => $value) {
            $$parameter = $value;
        }

        ob_start();

        try {
            include $phpFile;

            return ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    protected function compile(string $view, ?string $layout = null): void
    {
        $compiledFile = $this->compiledFilePath($view);
        $isDev        = $this->isDevEnvironment();

        if (! $isDev && file_exists($compiledFile)) {
            return;
        }

        if (! $isDev && ! file_exists($compiledFile)) {
            throw new HttpNotFoundException("Compiled view not found: {$compiledFile}");
        }

        $sourceFile = $this->resolveViewPath($view);

        $this->blocks       = [];
        $this->dependencies = [];

        $code = $this->composeTemplate(
            filePath: $sourceFile,
            forcedLayout: $layout,
            wrap: true
        );

        $code = $this->compileCode($code);

        /*
     * Esta validación es importante:
     * si queda @content, @AppName, @pageName, include, extends o yield,
     * entonces el motor realmente no terminó de compilar.
     */
        $this->assertNoPendingTemplateDirectives($code, $view);

        $this->ensureDirectory(dirname($compiledFile));

        $this->deleteViewsNotUsed();

        $result = file_put_contents(
            $compiledFile,
            '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code
        );

        if ($result === false || ! is_file($compiledFile)) {
            throw new \RuntimeException("Could not write compiled view: {$compiledFile}");
        }
    }

    /**
     * Compone una plantilla completa.
     *
     * Soporta:
     *
     * {% include includes/navbar.html %}
     * {% include includes/navbar/navbar.html %}
     * {% include shared/components/card %}
     * {% include "./card.html" %}
     * {% include "../partials/card.html" %}
     *
     * {% extends layouts/main.html %}
     * {% extends layouts/main %}
     * {% extends plantillas/admin/base %}
     *
     * Todo se resuelve siempre dentro de assets/views.
     */
    protected function composeTemplate(
        string $filePath,
        ?string $forcedLayout = null,
        bool $wrap = true,
        array $stack = []
    ): string {
        $filePath = $this->normalizeExistingPath($filePath);

        if (in_array($filePath, $stack, true)) {
            throw new \RuntimeException("Circular template reference detected: {$filePath}");
        }

        $stack[] = $filePath;

        $code       = $this->readTemplateFile($filePath);
        $currentDir = dirname($filePath);

        /*
         * El extends dentro del archivo tiene prioridad sobre el layout forzado.
         */
        $inlineExtends = $this->extractFirstDirectiveReference($code, 'extends');

        /*
         * Quitamos los extends para que no terminen convertidos a PHP.
         */
        $code = preg_replace($this->directivePattern('extends'), '', $code);

        /*
         * Primero resolvemos los includes del archivo actual.
         */
        $code = $this->compileIncludes($code, $currentDir, $stack);

        /*
         * Si es la vista principal y no tiene extends, usamos:
         *
         * 1. El layout enviado desde Response::view(..., layout: "...")
         * 2. El layout default.
         *
         * Si es un include interno, no se envuelve con layout.
         */
        $layoutReference = $inlineExtends;

        if ($layoutReference === null && $wrap) {
            $layoutReference = $forcedLayout ?? $this->defaultLayout;
        }

        if ($layoutReference !== null && $layoutReference !== '') {
            $layoutFile = $this->resolveLayoutPath($layoutReference, $currentDir);

            $layoutCode = $this->composeTemplate(
                filePath: $layoutFile,
                forcedLayout: null,
                wrap: false,
                stack: $stack
            );

            if (str_contains($layoutCode, $this->contentAnnotation)) {
                $code = str_replace($this->contentAnnotation, $code, $layoutCode);
            } else {
                $code = $layoutCode . PHP_EOL . $code;
            }
        }

        return $code;
    }

    protected function compileIncludes(string $code, string $currentDir, array $stack = []): string
    {
        return preg_replace_callback(
            $this->directivePattern('include'),
            function (array $matches) use ($currentDir, $stack) {
                $reference   = $this->referenceFromDirectiveMatch($matches);
                $includeFile = $this->resolveTemplatePath($reference, $currentDir);

                return $this->composeTemplate(
                    filePath: $includeFile,
                    forcedLayout: null,
                    wrap: false,
                    stack: $stack
                );
            },
            $code
        );
    }

    protected function compileCode(string $code): string
    {
        /*
     * Primero se procesan bloques/yields.
     */
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);

        /*
     * Directivas propias del framework.
     */
        $code = $this->compileCsrf($code);
        $code = $this->compileUrl($code);
        $code = $this->compileAppName($code);
        $code = $this->compilePageName($code);

        /*
     * Echos.
     */
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);

        /*
     * PHP libre.
     * Esto va al final porque cualquier {% ... %}
     * que quede se convierte a PHP.
     */
        $code = $this->compilePHP($code);

        return $code;
    }

    protected function resolveLayoutPath(string $reference, string $currentDir): string
    {
        $reference = $this->cleanReference($reference);

        if ($reference === '') {
            throw new \RuntimeException('Empty layout reference.');
        }

        $candidates = [];

        /*
     * Si el layout viene como ruta relativa:
     *
     * {% extends ./layout.html %}
     * {% extends ../layouts/base.html %}
     */
        if ($this->isRelativeReference($reference)) {
            $candidates[] = $currentDir . '/' . $reference;
        } else {
            /*
         * Forma flexible nueva:
         *
         * "error"              => views/error.html
         * "layouts/error"      => views/layouts/error.html
         * "plantillas/base"    => views/plantillas/base.html
         */
            $candidates[] = $this->viewsPath . '/' . ltrim($reference, '/');

            /*
         * Compatibilidad con tu forma anterior:
         *
         * Response::view(..., "error")
         *
         * Antes "error" significaba:
         * views/layouts/error.html
         *
         * Entonces agregamos este fallback SOLO si no mandaste una carpeta.
         */
            if (! str_contains($reference, '/')) {
                $candidates[] = $this->viewsPath . '/layouts/' . $reference;
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = $this->withHtmlExtension($candidate);

            if (is_file($candidate)) {
                return $this->normalizeExistingPath($candidate);
            }
        }

        $attempts = implode(', ', array_map(
            fn($candidate) => $this->withHtmlExtension($candidate),
            $candidates
        ));

        throw new \RuntimeException(
            "Template not found: {$reference}. Attempts: {$attempts}"
        );
    }

    protected function compileUrl(string $code): string
    {
        return str_replace($this->urlAnnotation, config('app.url'), $code);
    }

    protected function compileAppName(string $code): string
    {
        $appName = htmlspecialchars(
            (string) config('app.name'),
            ENT_QUOTES,
            'UTF-8'
        );

        return str_replace($this->appNameAnnotation, $appName, $code);
    }

    protected function compilePageName(string $code): string
    {
        return str_replace(
            $this->pageNameAnnotation,
            '<?php echo htmlspecialchars((string) ($pageName ?? ""), ENT_QUOTES, "UTF-8"); ?>',
            $code
        );
    }

    protected function compilePHP(string $code): string
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    protected function compileEchos(string $code): string
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    protected function compileEscapedEchos(string $code): string
    {
        return preg_replace(
            '~\{{{\s*(.+?)\s*\}}}~is',
            '<?php echo (!is_null($1) && $1!="") ? htmlspecialchars($1, ENT_QUOTES, "UTF-8") : "" ?>',
            $code
        );
    }

    protected function compileBlock(string $code): string
    {
        preg_match_all(
            '/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is',
            $code,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $value) {
            $blockName = trim($value[1]);

            if (! array_key_exists($blockName, $this->blocks)) {
                $this->blocks[$blockName] = '';
            }

            if (strpos($value[2], '@parent') === false) {
                $this->blocks[$blockName] = $value[2];
            } else {
                $this->blocks[$blockName] = str_replace(
                    '@parent',
                    $this->blocks[$blockName],
                    $value[2]
                );
            }

            $code = str_replace($value[0], '', $code);
        }

        return $code;
    }

    protected function compileYield(string $code): string
    {
        foreach ($this->blocks as $block => $value) {
            $blockPattern = preg_quote($block, '/');

            $code = preg_replace(
                '/{% ?yield ?' . $blockPattern . ' ?%}/i',
                $value,
                $code
            );
        }

        /*
         * Si queda un yield sin block, se elimina.
         */
        $code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);

        return $code;
    }

    protected function compileCsrf(string $code): string
    {
        /*
         * Soporta:
         *
         * @csrf
         * @csrf()
         * @csrf('login')
         * @csrf("login")
         * @csrf($csrfKey)
         *
         * También:
         *
         * @csrfMeta
         * @csrfMeta()
         * @csrfMeta('contact-send')
         * @csrfMeta($csrfKey)
         */

        $code = preg_replace_callback(
            '/@csrfMeta(?![A-Za-z0-9_])(?:\((.*?)\))?/is',
            function ($matches) {
                $argument = trim($matches[1] ?? '');

                if ($argument === '') {
                    return '<?php echo csrf_meta(); ?>';
                }

                return "<?php echo csrf_meta({$argument}); ?>";
            },
            $code
        );

        $code = preg_replace_callback(
            '/@csrf(?![A-Za-z0-9_])(?:\((.*?)\))?/is',
            function ($matches) {
                $argument = trim($matches[1] ?? '');

                if ($argument === '') {
                    return '<?php echo csrf_field(); ?>';
                }

                return "<?php echo csrf_field({$argument}); ?>";
            },
            $code
        );

        return $code;
    }

    /**
     * Resuelve la vista principal que se manda desde:
     *
     * view('home')
     * view('pages/main/home')
     * view('pages/main/home.html')
     */
    protected function resolveViewPath(string $view): string
    {
        return $this->resolveTemplatePath($view, $this->viewsPath);
    }

    /**
     * Resuelve cualquier referencia de template dentro de assets/views.
     *
     * Reglas:
     *
     * "layouts/main"                 => assets/views/layouts/main.html
     * "includes/navbar/navbar"       => assets/views/includes/navbar/navbar.html
     * "pages/main/home"              => assets/views/pages/main/home.html
     * "/pages/main/home"             => assets/views/pages/main/home.html
     * "./card"                       => relativo al archivo actual
     * "../shared/card"               => relativo al archivo actual
     *
     * Nunca permite salir de assets/views.
     */
    protected function resolveTemplatePath(string $reference, string $currentDir): string
    {
        $reference = $this->cleanReference($reference);

        if ($reference === '') {
            throw new \RuntimeException('Empty template reference.');
        }

        $candidates = [];

        if ($this->isRelativeReference($reference)) {
            $candidates[] = $currentDir . '/' . $reference;
        } else {
            $candidates[] = $this->viewsPath . '/' . ltrim($reference, '/');
        }

        foreach ($candidates as $candidate) {
            $candidate = $this->withHtmlExtension($candidate);

            if (is_file($candidate)) {
                return $this->normalizeExistingPath($candidate);
            }
        }

        $attempts = implode(', ', array_map(
            fn($candidate) => $this->withHtmlExtension($candidate),
            $candidates
        ));

        throw new \RuntimeException(
            "Template not found: {$reference}. Attempts: {$attempts}"
        );
    }

    protected function compiledFilePath(string $view): string
    {
        $view = $this->normalizeViewName($view);

        return $this->compiledPath . '/' . $view . '.php';
    }

    protected function normalizeViewName(string $view): string
    {
        $view = $this->normalizeSlashes($view);
        $view = trim($view);
        $view = ltrim($view, '/');

        $view = preg_replace('/\.html$/i', '', $view);

        if ($view === '' || $view === null) {
            throw new \RuntimeException('View name is empty.');
        }

        if ($this->hasTraversal($view)) {
            throw new \RuntimeException("Invalid view path: {$view}");
        }

        return $view;
    }

    protected function normalizeRootPath(string $path): string
    {
        $path = $this->normalizeSlashes($path);
        $real = realpath($path);

        if ($real !== false) {
            return rtrim($this->normalizeSlashes($real), '/');
        }

        return rtrim($path, '/');
    }

    protected function normalizeExistingPath(string $path): string
    {
        $realPath = realpath($path);

        if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
            throw new \RuntimeException("Template file not readable: {$path}");
        }

        $realPath = $this->normalizeSlashes($realPath);

        if (! $this->isInsideViewsPath($realPath)) {
            throw new \RuntimeException("Template path outside views directory: {$realPath}");
        }

        return $realPath;
    }

    protected function readTemplateFile(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException("Cannot read template file: {$path}");
        }

        $this->dependencies[$this->normalizeSlashes($path)] = filemtime($path) ?: time();

        return $content;
    }

    protected function directivePattern(string $directive): string
    {
        $directive = preg_quote($directive, '/');

        /*
         * Soporta:
         *
         * {% include path/to/file %}
         * {% include 'path/to/file' %}
         * {% include "path/to/file" %}
         */
        return '/{%\s*' . $directive . '\s+(?:(["\'])(.*?)\1|([^\s%]+))\s*%}/i';
    }

    protected function extractFirstDirectiveReference(string $code, string $directive): ?string
    {
        if (! preg_match($this->directivePattern($directive), $code, $matches)) {
            return null;
        }

        return $this->referenceFromDirectiveMatch($matches);
    }

    protected function referenceFromDirectiveMatch(array $matches): string
    {
        $quoted = $matches[2] ?? null;
        $plain  = $matches[3] ?? null;

        if (is_string($quoted) && trim($quoted) !== '') {
            return $this->cleanReference($quoted);
        }

        if (is_string($plain) && trim($plain) !== '') {
            return $this->cleanReference($plain);
        }

        return '';
    }

    protected function cleanReference(string $reference): string
    {
        $reference = $this->normalizeSlashes($reference);
        $reference = trim($reference);
        $reference = trim($reference, '\'"');

        if (str_contains($reference, "\0")) {
            throw new \RuntimeException('Invalid template reference.');
        }

        return $reference;
    }

    protected function withHtmlExtension(string $path): string
    {
        $path = $this->normalizeSlashes($path);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            return $path . '.html';
        }

        if ($extension !== 'html') {
            throw new \RuntimeException("Only .html templates are allowed: {$path}");
        }

        return $path;
    }

    protected function isRelativeReference(string $reference): bool
    {
        return str_starts_with($reference, './') || str_starts_with($reference, '../');
    }

    protected function hasTraversal(string $path): bool
    {
        $path = $this->normalizeSlashes($path);

        return preg_match('~(^|/)\.\.(/|$)~', $path) === 1;
    }

    protected function isInsideViewsPath(string $path): bool
    {
        $path = rtrim($this->normalizeSlashes($path), '/');
        $root = rtrim($this->normalizeSlashes(realpath($this->viewsPath) ?: $this->viewsPath), '/');

        $pathCompare = strtolower($path);
        $rootCompare = strtolower($root);

        return $pathCompare === $rootCompare || str_starts_with($pathCompare, $rootCompare . '/');
    }

    protected function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    protected function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    protected function isDevEnvironment(): bool
    {
        $env = strtolower((string) config('app.env'));

        return str_contains($env, 'dev') || str_contains($env, 'local');
    }

    protected function deleteViewsNotUsed(): void
    {
        if (! is_dir($this->compiledPath)) {
            return;
        }

        $htmlViews = [];

        foreach ($this->recursiveFiles($this->viewsPath, 'html') as $htmlFile) {
            $htmlFile = $this->normalizeSlashes($htmlFile);

            $relative = $this->relativePath($this->viewsPath, $htmlFile);
            $relative = $this->normalizeSlashes($relative);

            /*
         * Si la raíz de vistas es assets/views, entonces sí ignoramos:
         *
         * assets/views/compiled/...
         *
         * Pero si la raíz de vistas YA ES assets/views/compiled,
         * entonces NO debemos ignorar sus archivos.
         */
            if (str_starts_with($relative, 'compiled/')) {
                continue;
            }

            $relative = preg_replace('/\.html$/i', '', $relative);

            $htmlViews[$relative] = true;
        }

        foreach ($this->recursiveFiles($this->compiledPath, 'php') as $phpFile) {
            $phpFile = $this->normalizeSlashes($phpFile);

            $relative = $this->relativePath($this->compiledPath, $phpFile);
            $relative = preg_replace('/\.php$/i', '', $relative);

            if (! isset($htmlViews[$relative])) {
                @unlink($phpFile);
            }
        }
    }

    protected function recursiveFiles(string $directory, string $extension): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== strtolower($extension)) {
                continue;
            }

            $files[] = $this->normalizeSlashes($file->getPathname());
        }

        return $files;
    }

    protected function relativePath(string $root, string $path): string
    {
        $root = rtrim($this->normalizeSlashes($root), '/') . '/';
        $path = $this->normalizeSlashes($path);

        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }

        return basename($path);
    }

    protected function assertNoPendingTemplateDirectives(string $code, string $view): void
    {
        $pending = [];

        if (str_contains($code, $this->contentAnnotation)) {
            $pending[] = $this->contentAnnotation;
        }

        if (str_contains($code, $this->appNameAnnotation)) {
            $pending[] = $this->appNameAnnotation;
        }

        if (str_contains($code, $this->pageNameAnnotation)) {
            $pending[] = $this->pageNameAnnotation;
        }

        if (preg_match('/{%\s*(include|extends|yield|block|endblock)\b/i', $code, $matches)) {
            $pending[] = '{% ' . $matches[1] . ' ... %}';
        }

        if (empty($pending)) {
            return;
        }

        throw new \RuntimeException(
            'StencilEngine did not finish compiling view [' . $view . ']. Pending directives: ' .
            implode(', ', array_unique($pending))
        );
    }
}
