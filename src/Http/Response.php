<?php
namespace Whis\Http;

use Whis\View\ViewEngine;

class Response
{

    /**
     * response HTTP status code
     *
     * @var integer
     */
    protected int $status = 200;

    /**
     * response HTTP headers
     *
     * @var array<string,string>
     */
    protected array $headers = [];

    /**
     * response content
     *
     * @var string
     */
    protected ?string $content = null;

    //Getter and Setter for status

    /**
     * Get response HTTP status code
     *
     * @return integer
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Set response HTTP status code
     *
     * @param integer $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get response HTTP headers
     *
     * @return self
     */
    public function headers(?string $key = null): array | string | null
    {
        if (is_null($key)) {
            return $this->headers;
        }

        return $this->headers[strtolower($key)] ?? null;
    }

    /**
     * Set response HTTP header
     *
     * @param string $header
     * @param string $value
     * @return self
     */
    public function setHeader(string $header, string $value): self
    {
        $this->headers[strtolower($header)] = $value;
        return $this;
    }

    /**
     * Remove response HTTP header
     * @param string $header
     * @return void
     */
    public function removeHeader(string $header)
    {
        unset($this->headers[strtolower($header)]);
    }

    /**
     * Set HTTP header Content-Type
     *
     * @return self
     */
    public function setContentType(string $value): self
    {
        $this->setHeader('Content-Type', $value);
        return $this;
    }

    /**
     * Get response content
     *
     * @return string
     */
    public function content(): ?string
    {
        return $this->content;
    }

    /**
     * Set response content
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Prepare response to be sent, setting default Content-Type header
     * @return void
     */
    public function prepare()
    {
        $this->removeHeader("Pragma");
        if (is_null($this->content)) {
            $this->removeHeader('Content-Type');
            $this->removeHeader('Content-Length');
        } else {
            $this->setHeader('Content-length', strlen($this->content));
        }
    }

    /**
     * Create a new response with Content-Type: json
     *
     * @param array $data
     * @return self
     */
    public static function json(array $data): self
    {
        array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                $encoding = mb_detect_encoding($item, mb_detect_order(), true) ?: 'UTF-8';
                $item     = mb_convert_encoding($item, 'UTF-8', $encoding);
            }
        });

        return (new self())
            ->setContentType('application/json; charset=UTF-8')
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Create a new response with Content-Type: text/plain
     *
     * @param string $text
     * @return self
     */
    public static function text(string $text): self
    {
        $encoding = mb_detect_encoding($text, mb_detect_order(), true) ?: 'UTF-8';
        $text     = mb_convert_encoding($text, 'UTF-8', $encoding);

        return (new self())
            ->setContentType('text/plain; charset=UTF-8')
            ->setContent($text);
    }
    /**
     * Redirect to another URL or URI
     *
     * @param string $uri
     * @return self
     */
    public static function redirect(string $uri): self
    {
        return (new self())
            ->setStatus(302)
            ->setHeader('Location', $uri);
    }

    public static function view(
        string $view,
        array | string $parameters = [],
        ?string $layout = null,
        ?string $pageName = null
    ): self {
        /*
     * Permite:
     *
     * view('home', 'Inicio')
     *
     * En ese caso, el segundo argumento NO son parameters,
     * sino el pageName.
     */
        if (is_string($parameters)) {
            $pageName   = $parameters;
            $parameters = [];
        }

        $content = app(ViewEngine::class)->render(
            $view,
            $parameters,
            $layout,
            $pageName
        );

        return (new self())
            ->setStatus(200)
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setContentType('text/html')
            ->setHeader("Keep-Alive", "timeout=5, max=100")
            ->setHeader("Cache-Control", "private, max-age=86400, stale-while-revalidate=604800")
            ->setContent($content);
    }

    public function withErrors(array $errors, int $status = 400): self
    {
        $this->setStatus($status);
        session()->flash('_errors', $errors);
        session()->flash('_old', request()->data());
        return $this;
    }public static function file(
        string $path,
        bool $download = false,
        ?string $downloadName = null,
        bool $cache = false
    ): self {
        $realPath = realpath($path);

        if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
            return self::text('404 Not Found')->setStatus(404);
        }

        $content = file_get_contents($realPath);

        if ($content === false) {
            return self::text('404 Not Found')->setStatus(404);
        }

        $response = (new self())
            ->setStatus(200)
            ->setContentType(self::mimeType($realPath))
            ->setContent($content)
            ->setHeader(
                'Cache-Control',
                $cache
                    ? 'public, max-age=86400, stale-while-revalidate=604800'
                    : 'private, no-store, max-age=0'
            );

        if ($download) {
            $response->setHeader(
                'Content-Disposition',
                'attachment; filename="' . addslashes($downloadName ?: basename($realPath)) . '"'
            );
        }

        return $response;
    }

    private static function mimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'text/javascript; charset=UTF-8',
            'mjs'   => 'text/javascript; charset=UTF-8',
            'html'  => 'text/html; charset=UTF-8',
            'xml'   => 'application/xml; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'txt'   => 'text/plain; charset=UTF-8',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'avif'  => 'image/avif',
            'png'   => 'image/png',
            'jpg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'pdf'   => 'application/pdf',
            'mp4'   => 'video/mp4',
            'webm'  => 'video/webm',
            'ogg'   => 'video/ogg',
            default => self::detectMimeType($path),
        };
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        return $mime ?: 'application/octet-stream';
    }

}
