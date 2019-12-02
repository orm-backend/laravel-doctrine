<?php

namespace ItAces\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ArrayOrStringIn implements Rule
{
    
    /**
     * 
     * @var string[]
     */
    protected $allowed;
    
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }
    
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        
        return array_intersect($value, $this->allowed) == $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.arrayorstringin');
    }

}
