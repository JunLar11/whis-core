<?php

namespace Whis\Validation\Rules;


class LessThanRule implements ValidationRule
{
    public function __construct(private float $lessThan)
    {
        $this->lessThan = $lessThan;
    }

    public function message(): string
    {
        return "Must be a numeric value less than 5";
    }

    public function isValid($field, $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        return isset($data[$field])
            && is_numeric($data[$field])
            && $data[$field] < $this->lessThan;
    }
}
