<?php

namespace ItAces\Repositories;

use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * This repository always joins all related entities and can join all related collections.
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class WithJoinsRepository extends Repository
{
    
    /**
     * 
     * @var boolean
     */
    protected $joinCollections;
    
    /**
     * 
     * @param bool $joinCollections
     * @param bool $cacheable
     */
    public function __construct(bool $joinCollections = false, bool $cacheable = false) {
        parent::__construct($cacheable);
        $this->joinCollections = $joinCollections;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Repositories\Repository::getQuery()
     */
    public function getQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $parameters = $this->appendAdditionalParameters($class, $parameters, $alias);
        
        return parent::getQuery($class, $parameters, $alias);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Repositories\Repository::createQuery()
     */
    public function createQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $parameters = $this->appendAdditionalParameters($class, $parameters, $alias);
        
        return parent::createQuery($class, $parameters, $alias);
    }

    /**
     * 
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return array
     */
    protected function appendAdditionalParameters(string $class, array $parameters = [], string $alias = null) : array
    {
        $alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
        $classMetadata = $this->em->getClassMetadata($class);
        
        if (!array_key_exists('select', $parameters)) {
            $parameters['select'] = [];
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            if (!$this->joinCollections && $associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                continue;
            }
            
            $fieldName = "{$alias}.{$associationMapping['fieldName']}";
            
            if (!array_key_exists($fieldName, $parameters['select']) && array_search($fieldName, $parameters['select']) === false) {
                $parameters['select'][] = $fieldName;
            }
        }
        
        return $parameters;
    }

}
