<?php

namespace ItAces\Types;

use ItAces\UnderAdminControl;

interface FileType extends UnderAdminControl
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

}
