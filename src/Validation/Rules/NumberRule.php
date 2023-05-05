<?php

namespace Whis\Validation\Rules;

class NumberRule implements ValidationRule
{
    public function message(): string
    {
        return "Must be a number";
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        return isset($data[$field]) && is_numeric($data[$field]);
    }
}
