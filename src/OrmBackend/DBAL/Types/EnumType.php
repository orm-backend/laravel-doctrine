<?php

namespace OrmBackend\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
abstract class EnumType extends Type
{
    abstract public function getTypeName();
    
    abstract public function getAllowedValues();

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $values = array_map(function($val) { return "'".$val."'"; }, $this->getAllowedValues());

        return "ENUM(".implode(", ", $values).")";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!in_array($value, $this->getAllowedValues())) {
            throw new \InvalidArgumentException("Invalid '".$this->getTypeName()."' value.");
        }
        
        return $value;
    }

    public function getName()
    {
        return $this->getTypeName();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
