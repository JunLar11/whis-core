<?php

namespace Whis\Server;

use GuzzleHttp\Psr7\Header;
use Whis\Http\HttpMethod;
use Whis\Http\Request;
use Whis\Http\Response;
use Whis\Storage\File;
use Whis\Validation\Exceptions\ValidationException;

class PhpNativeServer implements Server
{

        /**
     * Get files from `$_FILES` global.
     *
     * @return array<string, \Chomsky\Storage\File>
     */
    protected function uploadedFiles(): array {
        $files = [];
        $file_number=0;
        $total_size=0;
        $maxFileUpload=ini_get('max_file_uploads');
        $uploadMaxFilesize=return_bytes(ini_get('upload_max_filesize'));
        $postMaxSize=return_bytes(ini_get('post_max_size'));
        foreach ($_FILES as $key => $file) {
            if (!empty($file["tmp_name"])) {
                if(is_array($file["tmp_name"])) {
                    $files[$key]=[];
                    $file_number+=count($file["tmp_name"]);
                    if($file_number > ($maxFileUpload-1)) {
                        header("HTTP/1.1 400 Bad Request");
                        header("/");
                    }
                    foreach($file["tmp_name"] as $index => $value) {
                        if(empty($file["tmp_name"][$index])||$value==""){
                            continue;
                        }
                        if($uploadMaxFilesize < $file["size"][$index])
                        {
                            //echo return_bytes(ini_get('post_max_size'));
                            header("HTTP/1.1 400 Bad Request");
                            header("/");
                        }
                        $total_size+=$file["size"][$index];
                        if($postMaxSize < $total_size)
                        {
                            header("HTTP/1.1 400 Bad Request");
                            header("/");
                        }

                        $files[$key][$index] = new File(
                            file_get_contents($file["tmp_name"][$index]),
                            $file["type"][$index],
                            $file["name"][$index],
                            $file["size"][$index],
                        );

                    }
                    continue;
                }
                if($uploadMaxFilesize < $file["size"])
                {
                    //echo return_bytes(ini_get('post_max_size'));
                    header("HTTP/1.1 400 Bad Request");
                    header("/");
                }
                $file_number++;
                if($file_number >($maxFileUpload-1)) {
                    header("HTTP/1.1 400 Bad Request");
                    header("/");
                }
                $total_size+=$file["size"];
                if($postMaxSize < $total_size)
                {
                    header("HTTP/1.1 400 Bad Request");
                    header("/");
                }
                $files[$key] = new File(
                    file_get_contents($file["tmp_name"]),
                    $file["type"],
                    $file["name"],
                    $file["size"],
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
        //$query_string=parse_url($_SERVER['QUERY_STRING'], PHP_URL_PATH);
        return (new Request())
            ->setUri($uri)
            ->setMethod(HttpMethod::from($_SERVER['REQUEST_METHOD']))
            ->setHeaders(getallheaders())
            ->setData($this->requestData())
            ->setQuery($this->getQueryStringVariables($uri))
            ->setFiles($this->uploadedFiles());

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
        
        if(strpos(parse_url($_SERVER['QUERY_STRING'], PHP_URL_PATH), '&') == false){
            return [];
        }
        //var_dump($url);
        $query_string = str_replace($url."&","", "/".parse_url($_SERVER['QUERY_STRING'], PHP_URL_PATH));
        //var_dump($query_string);
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
