<?php

namespace Whis\Validation\Rules;

class FilesizeRule implements ValidationRule
{
    public function __construct(private float $lessThan)
    {
        $this->lessThan = $lessThan;
    }

    public function message(): string
    {
        return "The filesize must be less than ".$this->lessThan;
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        //var_dump($data[$field]);
        return isset($data[$field])
            && is_numeric($data[$field])
            && $data[$field] < $this->lessThan;
    }
}
