<?php

namespace OrmBackend\Web\Fields;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use OrmBackend\ORM\Entities\Entity;

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
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param int $index
     * @return \OrmBackend\Web\Fields\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, Entity $entity = null, int $index = null)
    {
        $instance = parent::getInstance($classMetadata, $fieldName, $entity, $index);
        
        if ($entity && array_search($fieldName, FieldContainer::FORBIDDEN_FIELDS) === false) {
            $instance->value = $classMetadata->getFieldValue($entity, $instance->name);
        }
        
        return $instance;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\Web\Fields\MetaField::__construct()
     */
    protected function __construct(ClassMetadata $classMetadata, string $fieldName, int $index = null)
    {
        parent::__construct($classMetadata, $fieldName, $index);
        $this->fieldMapping = $classMetadata->getFieldMapping($this->name);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\Web\Fields\MetaField::getHtmlType()
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
     * @see \OrmBackend\Web\Fields\MetaField::getDefaultSortable()
     */
    protected function getDefaultSortable()
    {
        empty($this->fieldMapping['length']) || $this->fieldMapping['length'] <= 255 ? 'true' : 'false';
    }

}
