<?php

namespace ItAces\Types;

use ItAces\Publishable;

interface FileType extends Publishable
{
    /**
     * Get request validation rules
     * @return array
     */
    public static function getRequestValidationRules();
    
    public function setName(string $name);
    
    public function getName();
    
    public function setPath(string $path);
    
    public function getPath();

    public function url();
    
}
