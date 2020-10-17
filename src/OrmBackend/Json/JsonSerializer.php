<?php

namespace OrmBackend\Json;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use OrmBackend\ORM\Entities\Entity;
use JsonSerializable;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class JsonSerializer implements JsonSerializable
{
    /**
     * 
     * @var \OrmBackend\ORM\Entities\Entity
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
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param string[] $additional
     */
    public function __construct(EntityManager $em, Entity $entity, array $additional = [])
    {
        $this->em = $em;
        $this->classMetadata = $this->em->getClassMetadata(get_class($entity));
        $this->entity = $entity;
        $this->additional = $additional;
    }
    
    /**
     * 
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param array $additional
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return \stdClass
     */
    static public function toJson(Entity $entity, ClassMetadata $classMetadata, array $additional = [])
    {
        $object = new \stdClass;
        $em = app('em');
        $className = $classMetadata->name;
        $hidden = $className::$hidden ?? [];
        
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];

            if (in_array($fieldName, $hidden)) {
                continue;
            }
            
            $object->{$fieldName} = $classMetadata->getFieldValue($entity, $fieldName);
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];
            $targetMetadata = $em->getClassMetadata($associationMapping['targetEntity']);
            
            if (in_array($fieldName, $hidden)) {
                continue;
            }
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                /**
                 * @var \Doctrine\ORM\PersistentCollection $entities
                 */
                $collection = $classMetadata->getFieldValue($entity, $fieldName);
                
                if ($collection instanceof PersistentCollection) {
                    if (!$collection->isInitialized()) {
                        $object->{$fieldName} = [];
                        continue;
                    }
                }
                
                if (!$collection) {
                    $object->{$fieldName} = [];
                } else {
                    $object->{$fieldName} = JsonCollectionSerializer::toJson($collection, $targetMetadata);
                }
            } else if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $association = $classMetadata->getFieldValue($entity, $fieldName);
                
                if (!$association) {
                    $object->{$fieldName} = null;
                } else {
                    $object->{$fieldName} = static::toJson($association, $targetMetadata);
                }
            }
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
        return static::toJson($this->entity, $classMetadata, $this->additional);
    }

}
