<?php

namespace OrmBackend\Repositories;

/**
 * This repository allows you to specify which exactly related objects or collections should be joined.
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class JoinAwareRepository extends WithJoinsRepository
{
    
    /**
     * 
     * @var string[]
     */
    protected $joinRelations;
    
    /**
     * 
     * @param string[] $joinRelations
     */
    public function __construct(array $joinRelations = []) {
        parent::__construct();
        $this->joinRelations = $joinRelations;
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
            if (array_search($associationMapping['fieldName'], $this->joinRelations) === false) {
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
