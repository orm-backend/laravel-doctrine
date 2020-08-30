<?php

namespace VVK\Rules;

use Illuminate\Validation\Validator;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class PersistentCollection
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
        if (!($value instanceof \Doctrine\ORM\PersistentCollection) && !($value instanceof \Doctrine\Common\Collections\ArrayCollection)) {
            return false;
        }
        
        $class = array_key_exists(0, $parameters) ? $parameters[0] : null;
        $size = array_key_exists(1, $parameters) ? (int) $parameters[1] : null;
        $elements = $value->toArray();
        $valid = $size === null || count($elements) > $size - 1;
        
        return $valid && array_filter($elements, function($element) use ($class) {
            return $class === null || ($element instanceof $class);
        }) == $elements;
    }

}
