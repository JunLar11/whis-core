<?php

namespace Whis\Validation\Rules;

class EmailRule implements ValidationRule
{
    public function message(): string
    {
        return "The :field must be a valid email address";
    }

    public function isValid(string $field, array $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }
        $email=strtolower(trim($data[$field]));
        return (filter_var($email, FILTER_VALIDATE_EMAIL)!== false);
        // $split=explode("@",$email);

        // if(count($split)!=2){
        //     return false;
        // }

        // [$username,$domain]=$split;

        // $split=explode(".",$domain);

        // if(count($split)<2){
        //     return false;
        // }

        // [$label,$topLevelDomain]=$split;

        // return strlen($username)>=1 && strlen($label)>=1 && strlen($topLevelDomain)>=1;
    }
}
