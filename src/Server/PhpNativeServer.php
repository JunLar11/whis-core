<?php
namespace Whis\Server;

use Whis\Http\HttpMethod;
use Whis\Http\Request;
use Whis\Http\Response;
use Whis\Storage\File;

class PhpNativeServer implements Server
{
    /**
     * Get files from $_FILES global.
     *
     * Esta función NO valida.
     * Esta función NO redirige.
     * Esta función NO manda headers.
     *
     * Solo convierte $_FILES en objetos Whis\Storage\File.
     */
    protected function uploadedFiles(): array
    {
        $files = [];

        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'] ?? null)) {
                $files[$key] = [];

                foreach ($file['name'] as $index => $name) {
                    $uploadedFile = $this->makeUploadedFile($file, $index);

                    if ($uploadedFile !== null) {
                        $files[$key][] = $uploadedFile;
                    }
                }

                if (empty($files[$key])) {
                    unset($files[$key]);
                }

                continue;
            }

            $uploadedFile = $this->makeUploadedFile($file);

            if ($uploadedFile !== null) {
                $files[$key] = $uploadedFile;
            }
        }

        return $files;
    }

    private function makeUploadedFile(array $file, ?int $index = null): ?File
    {
        $isMultiple = $index !== null;

        $name = $isMultiple
            ? (string) ($file['name'][$index] ?? '')
            : (string) ($file['name'] ?? '');

        $type = $isMultiple
            ? (string) ($file['type'][$index] ?? '')
            : (string) ($file['type'] ?? '');

        $tmpName = $isMultiple
            ? (string) ($file['tmp_name'][$index] ?? '')
            : (string) ($file['tmp_name'] ?? '');

        $size = $isMultiple
            ? (int) ($file['size'][$index] ?? 0)
            : (int) ($file['size'] ?? 0);

        $error = $isMultiple
            ? (int) ($file['error'][$index] ?? UPLOAD_ERR_NO_FILE)
            : (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        /*
         * Si no se seleccionó archivo, no creamos File.
         * Esto permite que filesquantity:,2 acepte 0 archivos.
         */
        if ($error === UPLOAD_ERR_NO_FILE || $name === '') {
            return null;
        }

        /*
         * Si hubo error de subida, sí creamos File,
         * pero con el error guardado.
         *
         * Así el Validator puede decir:
         * - archivo demasiado grande
         * - error parcial
         * - etc.
         */
        $content = '';

        if ($error === UPLOAD_ERR_OK && $tmpName !== '' && is_file($tmpName)) {
            $content = file_get_contents($tmpName) ?: '';
        }

        return new File(
            $content,
            $type,
            $name,
            $size,
            $error
        );
    }

    protected function requestData(): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $contentType =
        $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? $headers['Content-Type'] ?? $headers['content-type'] ?? '';

        $isJson = stripos($contentType, 'application/json') !== false;

        if ($isJson) {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw ?: '', true);

            return is_array($data) ? $data : [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return is_array($_POST) ? $_POST : [];
        }

        parse_str(file_get_contents('php://input'), $data);

        return is_array($data) ? $data : [];
    }

    public function getRequest(): Request
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return (new Request())
            ->setUri($this->currentUri())
            ->setMethod(HttpMethod::from($_SERVER['REQUEST_METHOD']))
            ->setHeaders($headers)
            ->setData($this->requestData())
            ->setQuery($_GET)
            ->setFiles($this->uploadedFiles());
    }

    public function sendResponse(Response $response): void
    {
        /*
         * No fuerces Content-Type: None.
         * Solo deja que Response prepare sus headers.
         */
        $response->prepare();

        http_response_code($response->status());

        foreach ($response->headers() as $header => $value) {
            header("$header: $value");
        }

        print($response->content());
    }

    private function currentUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        return '/' . trim($path, '/');
    }
}
