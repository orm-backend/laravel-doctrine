<?php

namespace ItAces\View;

use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class BaseField extends MetaField
{

    public $value;

    public $disabled = false;
    
    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @return \ItAces\View\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, EntityBase $entity = null)
    {
        $instance = parent::getInstance($classMetadata, $fieldName);
        $instance->value = $classMetadata->getFieldValue($entity, $instance->name);
        
        if (array_search($instance->name, FieldContainer::INTERNAL_FIELDS) !== false) {
            $instance->disabled = true;
        }

        return $instance;
    }

}
