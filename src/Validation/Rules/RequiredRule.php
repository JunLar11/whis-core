<?php

namespace Whis\Validation\Rules;

class RequiredRule implements ValidationRule
{
    public function message(): string
    {
        return "The :field field is required";
    }
    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        return isset($data[$field]) && ($data[$field] !== ""||$data[$field] !== false||$data[$field]!==[]) && !is_null($data[$field]) && !empty($data[$field]);
    }
}
