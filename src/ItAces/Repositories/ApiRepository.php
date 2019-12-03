<?php

namespace ItAces\Repositories;

use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ItAces\ORM\QueryFactory;
use ItAces\ORM\Entities\EntityBase;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ApiRepository extends Repository
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Repositories\Repository::getQuery()
     */
    public function getQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $parameters = $this->appendAdditionalParameters($class, $parameters, $alias);
        
        return QueryFactory::fromArray($this->em, $class, $parameters, $alias)->createQueryBuilder()->getQuery();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Repositories\Repository::createQuery()
     */
    public function createQuery(string $class) : Query
    {
        $parameters = $this->appendAdditionalParameters($class);
        
        return QueryFactory::fromRequest($this->em, $class, $parameters)->createQueryBuilder()->getQuery();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Repositories\Repository::findOrFail()
     */
    public function findOrFail(string $class, int $id) : EntityBase
    {
        $element = $this->getQuery($class, [
            'filter' => [
                ['e.id', 'eq', $id]
            ]
        ], 'e')->getSingleResult();
        
        if (!$element) {
            abort(404, 'Not found.');
        }
        
        return $element;
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
            if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
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
