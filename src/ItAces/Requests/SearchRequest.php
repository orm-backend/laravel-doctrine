<?php

namespace ItAces\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
abstract class SearchRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract protected function getRules();
    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = $this->getRules();
        $rules['page'] = ['sometimes', 'integer', 'min:1'];
        
        return $rules;
    }
    
    /**
     * Get validated input only.
     *
     * @param  array|mixed|null  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $requested = parent::all();
        
        if (!$keys) {
            $result = [];
            $validated = array_intersect(array_keys($this->rules()), array_keys($requested));

            $parameters = parent::all($validated);
            
            foreach ($parameters as $key => $value) {
                if (is_null($value)) {
                    continue;
                }
                
                $result[$key] = $value;
            }
            
            return $result;
        }
        
        return $requested;
    }
    
    public function getNormolizedParameters()
    {
        $result = array();
        $parameters = $this->all();

        foreach ($parameters as $key => $value) {
            if ($key == 'order' || $key == 'page') {
                continue;
            }
            
            $key = str_replace('-', '.', $key);
            $result[$key] = $value;
        }
        
        return $result;
    }
    
}
