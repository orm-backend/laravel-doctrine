<?php

namespace VVK\Rules;

use Illuminate\Validation\Validator;
use Carbon\Carbon;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ArrayOrDate
{

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
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
        
        return array_filter($value, function($element) {
            $success = false;
            
            if (!is_string($element)) {
                return false;
            }
            
            try {
                Carbon::parse($element);
                $success = true;
            } catch (\Exception $e) {
            }
            
            return $success;
        }) == $value;
    }

}
