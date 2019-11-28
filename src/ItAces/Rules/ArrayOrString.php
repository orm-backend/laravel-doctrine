<?php

namespace App\Rules;

use Illuminate\Validation\Validator;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ArrayOrString
{
    
    /**
     * 
     * @var integer
     */
    protected $min;
    
    /**
     *
     * @var integer
     */
    protected $max;
    
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $min = null, int $max = null)
    {
        $this->min = $min !== null ? $min : 1;
        $this->max = $max !== null ? $max : 255;
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
        
        $min = $this->min;
        $max = $this->max;
        
        return array_filter($value, function($element) use ($min, $max) {
            if (!is_string($element)) {
                return false;
            }
            
            $length = strlen($element);
            
            return $length > $min - 1 && $length < $max + 1;
        }) == $value;
    }

}
