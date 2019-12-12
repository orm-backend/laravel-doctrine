<?php

namespace ItAces\View;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class BaseField extends MetaField
{

    /**
     * 
     * @var array
     */
    protected $fieldMapping;
    
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
        
        if ($entity && array_search($fieldName, FieldContainer::FORBIDDEN_FIELDS) === false) {
            $instance->value = $classMetadata->getFieldValue($entity, $instance->name);
        }
        
        return $instance;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\MetaField::__construct()
     */
    protected function __construct(ClassMetadata $classMetadata, string $fieldName)
    {
        parent::__construct($classMetadata, $fieldName);
        $this->fieldMapping = $classMetadata->getFieldMapping($this->name);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\MetaField::getHtmlType()
     */
    protected function getHtmlType()
    {
        switch ($this->fieldMapping['type']) {
            case Types::INTEGER:
            case Types::SMALLINT:
                // TODO case Types::FLOAT:
                // TODO case Types::DECIMAL:
                return 'number';
            case Types::BOOLEAN:
                return 'checkbox';
            case Types::DATE_MUTABLE:
                return 'date';
            case Types::DATETIME_MUTABLE:
            case Types::DATETIMETZ_MUTABLE:
                return 'datetime';
            case Types::TIME_MUTABLE:
                return 'time';
                break;
            case Types::TEXT:
                return 'textarea';
                break;
            case Types::STRING:
                return empty($this->fieldMapping['length']) || $this->fieldMapping['length'] > 255 ? 'textarea' : 'text';
                break;
        }
        
        return 'text';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\MetaField::getDefaultSortable()
     */
    protected function getDefaultSortable()
    {
        empty($this->fieldMapping['length']) || $this->fieldMapping['length'] <= 255 ? 'true' : 'false';
    }

}
