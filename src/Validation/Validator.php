<?php

namespace Whis\Validation;

use Whis\Validation\Exceptions\ValidationException;

class Validator
{
    /**
     * Arreglo de datos
     *
     * @var array
     */
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $validationRules, array $messages=[], bool $bachWithErrors=true): array
    {
        $validated=[];
        $errors=[];
        /*foreach ($validationRules as $field => $rules) {
            echo($rules.PHP_EOL);
        }
        exit;*/
        foreach ($validationRules as $field => $rules) {
            if(is_string($rules) && str_contains($rules,"|")){
                $rules=explode("|",$rules);
            }
            //var_dump($rules);
            //exit;
            if (!is_array($rules)) {
                $rules=[$rules];
            }
            $fieldUnderValidationErrors=[];
            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    $rule=Rule::from($rule);
                }

                if (!isset($this->data[$field])) {
                    $this->data[$field]=null;
                }

                if (!$rule->isValid($field, $this->data)) {
                    $message=$messages[$field][Rule::nameOf($rule)]??$rule->message();
                    $fieldUnderValidationErrors[Rule::nameOf($rule)]=str_replace(":field", $field, $message);
                }
            }
            if (count($fieldUnderValidationErrors)>0) {
                $errors[$field]=$fieldUnderValidationErrors;
            } else {
                $validated[$field]=$this->data[$field]??null;
            }
        }
        if (count($errors)>0) {
            if ($bachWithErrors) {
                throw new ValidationException($errors);
            }else{
                session()->flash('_errors', $errors);
                session()->flash('_old', request()->data());
            }
        }
        return $validated;
    }

}
