<?php

namespace Whis\Validation;

use Whis\Validation\Exceptions\ValidationException;

class Validator
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(
        array $validationRules,
        array $messages = [],
        bool $backWithErrors = true
    ): array {
        $validated = [];
        $errors = [];

        foreach ($validationRules as $field => $rules) {
            if (is_string($rules) && str_contains($rules, "|")) {
                $rules = explode("|", $rules);
            }

            if (!is_array($rules)) {
                $rules = [$rules];
            }

            $fieldUnderValidationErrors = [];

            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    $rule = Rule::from($rule);
                }

                if (!array_key_exists($field, $this->data)) {
                    $this->data[$field] = null;
                }

                if (!$rule->isValid($field, $this->data)) {
                    $ruleName = Rule::nameOf($rule);
                    $message = $messages[$field][$ruleName] ?? $rule->message();

                    $fieldUnderValidationErrors[$ruleName] = str_replace(
                        ":field",
                        $field,
                        $message
                    );
                }
            }

            if (count($fieldUnderValidationErrors) > 0) {
                $errors[$field] = $fieldUnderValidationErrors;
            } else {
                $validated[$field] = $this->data[$field] ?? null;
            }
        }

        if (count($errors) > 0) {
            if ($backWithErrors) {
                throw new ValidationException($errors);
            }

            session()->flash('_errors', $errors);
            session()->flash('_old', request()->data());
        }

        return $validated;
    }
}