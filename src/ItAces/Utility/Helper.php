<?php

namespace ItAces\Utility;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Helper
{
    
    /**
     * 
     * @param string $className
     * @return string
     */
    public static function classToUlr(string $className) : string
    {
        $result = [];
        $pieces = explode('\\', $className);
        
        foreach ($pieces as $piece) {
            $result[] = Str::snake($piece);
        }
        
        return implode('-', $result);
    }
    
    /**
     * 
     * @param string $classUrlName
     * @return string
     */
    public static function classFromUlr(string $classUrlName) : string
    {
        $result = [];
        $pieces = explode('-', $classUrlName);
        
        foreach ($pieces as $piece) {
            $result[] = ucfirst( Str::camel($piece) );
        }
        
        return implode('\\', $result);
    }
    
}