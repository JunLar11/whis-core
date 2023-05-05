<?php

namespace Whis\Container;

use Whis\Database\Model;
use Whis\Exceptions\HttpNotFoundException;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class DependencyInjection
{


    public static function resolveParameters(Closure|array $callback, $routeParams=[] ){
        $methodOrFunction=is_array($callback) ? new ReflectionMethod($callback[0],$callback[1]) : new ReflectionFunction($callback);
        $parameters=[];
        foreach($methodOrFunction->getParameters() as $parameter){
            //var_dump($parameter->getType()->getName());
            //echo "<br>";
            // var_dump(gettype($parameter));
            $resolved=null;
            if(is_subclass_of($parameter->getType()->getName(),Model::class)){
                
                $modelClass=new ReflectionClass($parameter->getType()->getName());
                
                $routeParamName=snake_case($modelClass->getShortName());
                $resolved=$parameter->getType()->getName()::find($routeParams[$routeParamName]??0);

                if(is_null($resolved)){
                    throw new HttpNotFoundException();
                }

            }elseif ($parameter->getType()->isBuiltin()) {

                $resolved=$routeParams[$parameter->getName()]??null;
                switch(true){
                    case (is_numeric($resolved)):
                        $resolved=(int)$resolved;
                        break;
                    case (is_bool($resolved)):
                        $resolved=(bool)$resolved;
                        break;
                    case (is_string($resolved)):
                        $resolved=(string)$resolved;
                        break;
                    case (is_null($resolved)):
                        $resolved=null;
                        break;
                    default:
                        $resolved=$resolved;
                }
                // var_dump(self::getTypeOfParameter($resolved));
                // echo "<br>";
                // var_dump($parameter->getType()->getName());
                
                if($parameter->getType()->getName()!=self::getTypeOfParameter($resolved)){
                    http_response_code(500);
                    exit;
                }
                

            }else{
                $resolved=app($parameter->getType()->getName());
            }
            // echo "<br>";
            // var_dump($resolved);
            // exit;
            $parameters[]=$resolved;
            
        }
        
        return $parameters;
    }

    protected static function getTypeOfParameter(mixed $parameter){
        switch(true){
            case (is_integer($parameter)):
                return "int";
            case (is_float($parameter)):
                return "float";
            case (is_double($parameter)):
                return "double";
            case (is_bool($parameter)):
                return "bool";
            case (is_string($parameter)):
                return "string";
            case (is_null($parameter)):
                return "null";
            case (is_array($parameter)):
                return "array";
            case (is_callable($parameter)):
                return "callable";
            case (is_object($parameter)):
                return get_class($parameter);
            default:
                return gettype($parameter);
        }
    }

}
