<?php

namespace ItAces\ORM;

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
        return 'd_' . parent::classToTableName($className);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Doctrine\ORM\Mapping\DefaultNamingStrategy::joinTableName()
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return 'd_' . parent::joinTableName($sourceEntity, $targetEntity, $propertyName);
    }
    
}