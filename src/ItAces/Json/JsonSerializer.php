<?php

namespace ItAces\Json;

use JsonSerializable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ItAces\ORM\Entities\EntityBase;
use ItAces\ORM\DevelopmentException;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class JsonSerializer implements JsonSerializable
{
    /**
     * 
     * @var \ItAces\ORM\Entities\EntityBase
     */
    protected $entity;
    
    /**
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * 
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;
    
    /**
     *
     * @var string[]
     */
    protected $additional;
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @param string[] $additional
     */
    public function __construct(EntityManager $em, EntityBase $entity, array $additional = [])
    {
        $this->em = $em;
        $this->classMetadata = $this->em->getClassMetadata(get_class($entity));
        $this->entity = $entity;
        $this->additional = $additional;
    }
    
    /**
     * 
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param array $additional
     * @throws \ItAces\ORM\DevelopmentException
     * @return \stdClass
     */
    static public function toJson(EntityBase $entity, ClassMetadata $classMetadata, array $additional = [])
    {
        $object = new \stdClass;
        $className = get_class($entity);
        $reflectionClass = new \ReflectionClass($className);
        $propertyHidden = $reflectionClass->hasProperty('hidden') ? $reflectionClass->getProperty('hidden') : null;
        $isHiddens = $propertyHidden && $propertyHidden->isStatic() && $propertyHidden->isPublic() && is_array($className::$hidden);
        
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];
            
            if ($isHiddens && array_search($fieldName, $className::$hidden) !== false) {
                continue;
            }
            
            $object->{$fieldName} = $classMetadata->getFieldValue($entity, $fieldName);
        }
        
        foreach ($additional as $fieldName) {
            $method = $reflectionClass->getMethod($fieldName);
            
            if (!$method) {
                throw new DevelopmentException("No such method '{$fieldName}' on entity '{$className}'.");
            }
            
            $object->{$fieldName} = $method->invoke($entity);
        }
        
        return $object;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $className = get_class($this->entity);
        $classMetadata = $this->em->getClassMetadata($className);
        $object = static::toJson($this->entity, $classMetadata, $this->additional);
        $reflectionClass = new \ReflectionClass($className);
        $propertyHidden = $reflectionClass->hasProperty('hidden') ? $reflectionClass->getProperty('hidden') : null;
        $isHiddens = $propertyHidden && $propertyHidden->isStatic() && $propertyHidden->isPublic() && is_array($className::$hidden);
        
        foreach ($this->classMetadata->associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];
            $targetMetadata = $this->em->getClassMetadata($associationMapping['targetEntity']);
            
            if ($isHiddens && array_search($fieldName, $className::$hidden) !== false) {
                continue;
            }
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                $entities = $this->classMetadata->getFieldValue($this->entity, $fieldName);
                
                if (!$entities) {
                    $object->{$fieldName} = [];
                } else {
                    $object->{$fieldName} = JsonCollectionSerializer::toJson($entities, $targetMetadata);
                }
            } else if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $entity = $this->classMetadata->getFieldValue($this->entity, $fieldName);
                
                if (!$entity) {
                    $object->{$fieldName} = null;
                } else {
                    $object->{$fieldName} = static::toJson($entity, $targetMetadata);
                }
            }
        }

        return $object;
    }

}
