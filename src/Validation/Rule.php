<?php

namespace Whis\Validation;

use Whis\Validation\Exceptions\RuleParseException;
use Whis\Validation\Exceptions\UnknownRuleException;
use Whis\Validation\Rules\EmailRule;
use Whis\Validation\Rules\LessThanRule;
use Whis\Validation\Rules\NumberRule;
use Whis\Validation\Rules\RequiredRule;
use Whis\Validation\Rules\RequiredWhenRule;
use Whis\Validation\Rules\ValidationRule;
use Whis\Validation\Rules\RequiredWithRule;
use Whis\Validation\Rules\FiletypeRule;
use ReflectionClass;

class Rule
{
    private static array $rules = [];

    private static array $defaultRules = [
        RequiredRule::class,
        RequiredWithRule::class,
        RequiredWhenRule::class,
        EmailRule::class,
        NumberRule::class,
        LessThanRule::class,
        FiletypeRule::class,
    ];

    public static function loadDefaultRules(): void
    {
        self::load(self::$defaultRules);
    }

    public static function load(array $rules): void
    {
        foreach ($rules as $class) {
            $className=array_slice(explode('\\', $class), -1)[0];
            $ruleName=snake_case($className);
            self::$rules[$ruleName]=$class;
        }
    }

    public static function nameOf(ValidationRule $rule): string
    {
        $class=new ReflectionClass($rule);
        return str_replace('_rule', '', snake_case($class->getShortName()));
    }



    public static function email(): ValidationRule
    {
        return new EmailRule();
    }

    public static function required(): ValidationRule
    {
        return new RequiredRule();
    }

    public static function requiredWith(string $withField): ValidationRule
    {
        return new RequiredWithRule($withField);
    }


    public static function number(): ValidationRule
    {
        return new NumberRule();
    }

    public static function lessThan(int|float $value): ValidationRule
    {
        return new LessThanRule($value);
    }

    public static function requiredWhen(
        string $otherField,
        string $operator,
        int|float $value
    ): ValidationRule {
        return new RequiredWhenRule($otherField, $operator, $value);
    }

    public static function parseBasicRule(string $rule): ValidationRule
    {
        $class=new ReflectionClass(self::$rules[$rule]);
        if (count($class->getConstructor()?->getParameters() ?? []) > 0) {
            throw new RuleParseException("Rule ". str_replace('_rule', '', $rule) ." requires parameters");
        }

        return $class->newInstance();
    }

    public static function parseRuleWithParams(string $rule, string $params): ValidationRule
    {
        $class=new ReflectionClass(self::$rules[$rule]);
        $constructorParameters=$class->getConstructor()?->getParameters()??[];
        $givenParams=array_filter(explode(",", $params), fn ($p) => !empty($p));
        if (count($givenParams)!=count($constructorParameters)) {
            throw new RuleParseException("Rule ".str_replace('_rule', '', $rule) ." requires ".count($constructorParameters)." parameters. ".count($givenParams)." given");
        }
        return $class->newInstance(...$givenParams);
    }

    public static function from(string $str): ValidationRule
    {
        if (strlen($str) === 0) {
            throw new RuleParseException("Rule string cannot be empty");
        }
        //var_dump(self::$rules);
        $ruleParts=explode(':', $str);
        $ruleParts[0]="{$ruleParts[0]}_rule";
        if (!array_key_exists($ruleParts[0], self::$rules)) {
            throw new UnknownRuleException("Rule ".str_replace('_rule', '', $ruleParts[0])." does not exist");
        }

        if (count($ruleParts) === 1) {
            return self::parseBasicRule($ruleParts[0]);
        }

        [$ruleName, $ruleParams]=$ruleParts;
        return self::parseRuleWithParams($ruleName, $ruleParams);
    }
}
