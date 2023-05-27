<?php

namespace Whis\Validation\Rules;

class FiletypeRule implements ValidationRule
{
    public function __construct(private string|array $filetype)
    {
        if(str_contains($filetype, "/"))
        {
            $filetype = explode("/", $filetype);
        }
        $this->filetype = $filetype;
    }

    public function message(): string
    {
        if(is_array($this->filetype)){
            return "Must be of type " . implode(", ", $this->filetype);
        }
        return "Must be of type " . $this->filetype;
        
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        if(is_array($this->filetype)){
            if(isset($data[$field])){
                foreach($this->filetype as $type){
                    if(str_contains($data[$field], $type)){
                        return true;
                    }
                }
                return false;
            }
            return false;
        }
        return isset($data[$field]) && str_contains($data[$field], $this->filetype);
    }
}
