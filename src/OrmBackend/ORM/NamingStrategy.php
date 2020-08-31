<?php

namespace OrmBackend\ORM;

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class NamingStrategy extends UnderscoreNamingStrategy
{
    /**
     * 
     * {@inheritDoc}
     * @see \Doctrine\ORM\Mapping\DefaultNamingStrategy::classToTableName()
     */
    public function classToTableName($className)
    {
        return config('itaces.table_prefix', 'd_') . parent::classToTableName($className);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Doctrine\ORM\Mapping\DefaultNamingStrategy::joinTableName()
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return config('itaces.table_prefix', 'd_') . parent::joinTableName($sourceEntity, $targetEntity, $propertyName);
    }
    
}