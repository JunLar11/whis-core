<?php

namespace Whis\Validation\Exceptions;

use Whis\Exceptions\WhisException;

class ValidationException extends WhisException
{
    /**
     * @var array
     */
    private $errors = [];
    public function __construct(array $errors)
    {
        $this->errors=$errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
