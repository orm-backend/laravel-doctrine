<?php

namespace VVK\Utility;

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
    public static function classToUrl(string $className) : string
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
    
    /**
     *
     * @param string $classUrlName
     * @return string
     */
    public static function classShortFromUrl(string $classUrlName) : string
    {
        $className = self::classFromUlr($classUrlName);
        
        return (new \ReflectionClass($className))->getShortName();
    }
    
    /**
     *
     * @param string $classUrlName
     * @return string
     */
    public static function aliasFromUrl(string $classUrlName) : string
    {
        $className = self::classFromUlr($classUrlName);
        
        return self::aliasFromClass($className);
    }
    
    /**
     *
     * @param string $classUrlName
     * @return string
     */
    public static function aliasFromClass(string $className) : string
    {
        $classShortName = (new \ReflectionClass($className))->getShortName();
        
        return lcfirst($classShortName);
    }
    
}