<?php

namespace Whis\Http;

use Whis\Routing\Route;
use Whis\Storage\File;
use Whis\Validation\Validator;

class Request
{
        /**
     * Request URI
     *
     * @var string
     */
    protected string $uri;

    /**
     * Route matched by URI
     *
     * @var Route
     */
    protected Route $route;

    /**
     * Request HTTP method
     *
     * @var HttpMethod
     */
    protected HttpMethod $method;

    /**
     * Request HTTP POST data
     *
     * @var array<string,string>
     */
    protected array $data;

    /**
     * Request HTTP query parameters
     *
     * @var array<string,string>
     */
    protected array $query;

    protected array $headers = [];


        /**
     * Uploaded files.
     *
     * @var array<string, \Lune\Storage\File>
     */
    protected array $files = [];


    /**
     * Get the value of uri
     *
     * @return string
     */

    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Set the value of uri
     * @param string $uri
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get the value of route
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * Set the value of route
     * @param Route $route
     * @return self
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Get the value of method
     * @return HttpMethod
     */
    public function method(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Set the value of method
     * @param HttpMethod $method
     * @return self
     */
    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Get the POST data or get one value by key
     *
     * @return array<string,string>
     */
    public function data(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }

    /**
     * Set the POST data
     * @param array<string,string> $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get ALL the query parameters
     *
     * @return array<string,string>
     */
    public function query(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->query;
        }
        return $this->query[$key] ?? null;
    }

    /**
     * Set the query parameters
     * @param array<string,string> $query
     * @return self
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get the value of headers
     *
     * @return array<string,string>
     */
    public function headers(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    /**
     * Set the value of headers
     *
     * @param array<string,string> $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->headers[strtolower($header)] = $value;
        }

        return $this;
    }

    /**
     * Get the route parameters
     * @param string|null $key
     * @return array<string,string>
     */
    public function routeParameters(?string $key = null): array|string|null
    {
        $parameters = $this->route->parseParameters($this->uri);
        if (is_null($key)) {
            return $parameters;
        }
        return $parameters[$key] ?? null;
    }

    public function validate(array $validationRules, bool $backWithErrors=true, array $messages = []): array
    {
        //($this->data);
        $data=$this->data;
        if(!is_null($this->files)){
            foreach($this->files as $key=>$file){
                if(is_array($file)){
                    if(empty($file)||!isset($file[0])||empty($file[0])){
                        $data[$key]=null;
                        continue;
                    }
                    
                    $data[$key]=$file[0]->originalName();
                    if($validationRules[$key]=="required"){
                        continue;
                    }
                    $i=0;
                    foreach($file as $k=>$f){
                        $data[$key."_".$i]=$f->originalName();
                        
                        if(isset($validationRules[$key])&&!empty($validationRules[$key])){
                            $validationRules[$key."_".$i]=$validationRules[$key];
                        }
                        $i++;
                    }
                    //unset($validationRules[$key]);
                    continue;
                }
                $data[$key]=$file->originalName();
            }
        }
        $validator = new Validator($data);
        return $validator->validate($validationRules, $messages, $backWithErrors);
    }

        /**
     * Get file from request.
     *
     * @param string $name
     * @return File|null
     */
    public function file(string $name, array|string $validate=null, bool $backWithErrors=true, array|string $messages = null): File|array|null{
        /**
         * [attribute=>rule]
         */

        if(is_null($validate)){
            return $this->files[$name] ?? null;
        }
        if(is_array($validate)){
            if(!is_null($this->files[$name])){
                if(is_array($this->files[$name])){
                    $all_files=[];
                    foreach($this->files[$name] as $file){
                        
                        foreach ($validate as $attribute => $rule) {
                            //var_dump($file->getAttribute($attribute));
                            $validator=new Validator([$name=>($file->getAttribute($attribute))]);
                            $all_files[]=$validator->validate([$name=>$rule],((is_array($messages)||is_null($messages))?[]:[$name=>[$validate[$name]=>$messages]]),$backWithErrors);
                        }
                        
                    }
                    $flag=true;
                    foreach($all_files as $file){
                        if(is_null($file)||$file==false){
                            $flag=false;
                        }
                    }
                    if($flag){
                        return $this->files[$name]??null;
                    }
                }
                
                foreach ($validate as $attribute => $rule) {
                    $validator=new Validator([$name=>($this->files[$name]->getAttribute($attribute))]);
                    $file=$validator->validate([$name=>$validate],((is_array($messages)||is_null($messages))?[]:[$name=>[$validate[$name]=>$messages]]),$backWithErrors);
                }
                
                if(!is_null($file)||$file==true){
                    return $this->files[$name]??null;
                }            
            }
        }
        if(!is_null($this->files[$name])){
            if(is_array($this->files[$name])){
                $all_files=[];
                foreach($this->files[$name] as $file){
                    $validator=new Validator([$name=>($file->originalName())]);
                    $all_files[]=$validator->validate([$name=>$validate],((is_array($messages)||is_null($messages))?[]:[$name=>[$validate[$name]=>$messages]]),$backWithErrors);
                }
                $flag=true;
                foreach($all_files as $file){
                    if(is_null($file)||$file==false){
                        $flag=false;
                    }
                }
                if($flag){
                    return $this->files[$name]??null;
                }
            }
            $validator=new Validator([$name=>($this->files[$name]->originalName())]);
            $file=$validator->validate([$name=>$validate],((is_array($messages)||is_null($messages))?[]:[$name=>[$validate[$name]=>$messages]]),$backWithErrors);
            if(!is_null($file)||$file==true){
                return $this->files[$name]??null;
            }            
        }
        return null;
    }

    /**
     * Set uploaded files.
     *
     * @param array<string, \Chomsky\Storage\File> $files
     * @return self
     */
    public function setFiles(array $files): self {
        $this->files = $files;
        return $this;
    }
}
