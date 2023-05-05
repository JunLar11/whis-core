<?php

namespace Whis\Validation\Rules;

interface ValidationRule
{
    public function message(): string;

    public function isValid(string $field, array $data): bool;
}
