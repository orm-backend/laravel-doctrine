<?php

namespace OrmBackend\ORM;

use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class QuoteStrategy extends AnsiQuoteStrategy
{

    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
    {
        $columnName = parent::getColumnName($fieldName, $class, $platform);
        
        return in_array($fieldName, $class->identifier) ? $columnName : $this->quote( $columnName );
    }

    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->quote( parent::getTableName($class, $platform) );
    }

//     public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform)
//     {
//         return $this->quote( parent::getSequenceName($definition, $class, $platform) );
//     }

    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->quote( parent::getJoinColumnName($joinColumn, $class, $platform) );
    }

    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->quote( parent::getReferencedJoinColumnName($joinColumn, $class, $platform) );
    }

    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $this->quote( parent::getJoinTableName($association, $class, $platform) );
    }

//     public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = null)
//     {
//         return $this->quote( parent::getColumnAlias($columnName, $counter, $platform, $class) );
//     }

    protected function quote(string $value) : string
    {
        return "`{$value}`";
    }

}
