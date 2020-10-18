<?php

namespace OrmBackend\Json;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use OrmBackend\ORM\QueryFactory;
use OrmBackend\ORM\Entities\Entity;
use OrmBackend\Utility\Helper;
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
     * @param \OrmBackend\ORM\Entities\Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
    
    /**
     *
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param string $path
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return \stdClass
     */
    static public function toJson(Entity $entity, string $path)
    {
        $object = new \stdClass;
        $em = app('em');
        $select = QueryFactory::lastSelect();
        $className = get_class($entity);
        $classMetadata = $em->getClassMetadata($className);
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
            
            if (!in_array($path . '.' . $fieldName, $select)) {
                continue;
            }
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                $collection = $classMetadata->getFieldValue($entity, $fieldName);

                if (!$collection) {
                    $object->{$fieldName} = [];
                } else {
                    $object->{$fieldName} = JsonCollectionSerializer::toJson($collection, $path . '.' . $fieldName);
                }
            } else if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $association = $classMetadata->getFieldValue($entity, $fieldName);

                if ($association === null) {
                    $object->{$fieldName} = null;
                } else {
                    $object->{$fieldName} = static::toJson($association, $path . '.' . $fieldName);
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
        return static::toJson($this->entity, Helper::aliasFromClass(get_class($this->entity)));
    }
    
}
