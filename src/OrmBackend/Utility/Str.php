<?php

namespace OrmBackend\Utility;

class Str extends \Illuminate\Support\Str
{
    
    /**
     * Pluralize the last word of an English, word sequence.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function pluralCamelWords($value, $count = 2)
    {
        $parts = preg_split('/((?:^|[A-Z])[a-z]+)/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $lastWord = array_pop($parts);
        $parts[] = self::plural($lastWord, $count);
        
        return implode(' ', $parts);
    }
    
}