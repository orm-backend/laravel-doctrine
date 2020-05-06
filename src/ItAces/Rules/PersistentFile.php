<?php

namespace ItAces\Rules;

use Illuminate\Validation\Validator;
use ItAces\Types\FileType;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class PersistentFile
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
        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        $class = $parameters[0];
        $size = array_key_exists(1, $parameters) ? (int) $parameters[1] : null;
        $mimetypes = array_key_exists(2, $parameters) ? explode(';', $parameters[2]) : null;
        
        if (is_numeric($value)) {
            $value = (int) $value;
            
            if (!$value) {
                return false;
            }
            
            return (bool) $em->getRepository($class)->find($value);
        }
        
        if (!($value instanceof $class)) {
            return false;
        }
        
        if (!($value instanceof FileType)) {
            return false;
        }
        
        if (!$size && !$mimetypes) {
            return true;
        }
        
        /**
         * 
         * @var \ItAces\Types\FileType $file
         */
        $file = $value;
        $path = public_path($file->getPath());
        
        if (!file_exists($path)) {
            return false;
        }
        
        if ($mimetypes) {
            $mime = file_mimetype($path);
            
            if (array_search($mime, $mimetypes) === false) {
                return false;
            }
        }
        
        if ($size && ($size * 1000000) < filesize($path)) {
            return false;
        }
        
        return true;
    }
}
