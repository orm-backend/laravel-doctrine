<?php

namespace OrmBackend\Rules;

use Illuminate\Validation\Validator;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ArrayOrString
{
    
    /**
     * Determine if the validation rule passes.
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return boolean
     */
    public function validate($attribute, $value, $parameters, Validator $validator)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        
        $min = array_key_exists(0, $parameters) ? (int) $parameters[0] : null;
        $max = array_key_exists(1, $parameters) ? (int) $parameters[1] : null;
        
        return array_filter($value, function($element) use ($min, $max) {
            if (!is_string($element)) {
                return false;
            }
            
            $length = strlen($element);
            
            return ($min === null || $length > $min - 1) && ($max === null && $length < $max + 1);
        }) == $value;
    }

}
