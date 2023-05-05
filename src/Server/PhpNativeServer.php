<?php

namespace Whis\Server;

use Whis\Http\HttpMethod;
use Whis\Http\Request;
use Whis\Http\Response;
use Whis\Storage\File;

class PhpNativeServer implements Server
{

        /**
     * Get files from `$_FILES` global.
     *
     * @return array<string, \Chomsky\Storage\File>
     */
    protected function uploadedFiles(): array {
        $files = [];
        foreach ($_FILES as $key => $file) {
            if (!empty($file["tmp_name"])) {
                $files[$key] = new File(
                    file_get_contents($file["tmp_name"]),
                    $file["type"],
                    $file["name"],
                );
            }
        }

        return $files;
    }

    protected function requestData(): array {
        $headers = getallheaders();

        $isJson = isset($headers["Content-Type"])
            && $headers["Content-Type"] === "application/json";


        if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isJson) {
            return $_POST;
        }

        if ($isJson) {
            $data = json_decode(file_get_contents("php://input"), associative: true);
        } else {
            parse_str(file_get_contents("php://input"), $data);
        }

        return $data;
    }

    /**
     * Get the request sent by the client.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        //var_dump($_SERVER);
        $uri="/".$this->removeQueryStringVariables(parse_url($_SERVER['QUERY_STRING'], PHP_URL_PATH));
        return (new Request())
            ->setUri($uri)
            ->setMethod(HttpMethod::from($_SERVER['REQUEST_METHOD']))
            ->setHeaders(getallheaders())
            ->setData($this->requestData())
            ->setQuery($this->getQueryStringVariables($uri));
    }


    /**
     * Send a response to the client
     *
     * @param Response $response
     * @return void
     */
    public function sendResponse(Response $response)
    {
        //PHP  envia Content-Type: text/html; charset=UTF-8 por defecto, pero si no hay contenido, no debería enviarlo
        //Content-Type no puede ser removido si no hay un valor previo
        header("Content-Type: None");
        header_remove("Content-Type");

        $response->prepare();
        http_response_code($response->status());
        foreach ($response->headers() as $header => $value) {
            header("$header: $value");
        }
        print($response->content());
    }

    protected function getQueryStringVariables(string $url):array
    {
        if(strpos($url, '&') == false){
            return [];
        }
        $query_string = str_replace($url."&","", $_SERVER["QUERY_STRING"]);
        $query_array=[];
        if (strpos($query_string, '&') !== false) {
            $query_string = explode('&', $query_string);
            foreach ($query_string as $value) {
                $query_element = explode('=', $value);
                $query_array[$query_element[0]] = $query_element[1];
            }
            return $query_array;
        }
        $query_string = explode('=', $query_string);
        $query_array[$query_string[0]] = $query_string[1];
        return $query_array;

    }

    protected function removeQueryStringVariables(string $url)
    {
        if ($url != '') {
            $parts = explode('&', $url, 2);
            if (strpos($parts[0], '=') === false) {
                $url = $parts[0];
            } else {
                $url = '';
            }
        }
        return $url;
    }
}
