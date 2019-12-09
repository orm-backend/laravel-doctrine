<?php

namespace ItAces\View;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class MetaField
{
    
    public $class;
    
    public $name;
    
    public $fullname;
    
    public $title;
    
    public $type;
    
    public $sortable;
    
    public $textalign;
    
    public $width;
    
    public $autohide;
    
    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @return \ItAces\View\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, EntityBase $entity = null)
    {
        $instance = new static();
        $instance->name = $fieldName;
        $instance->class = $classMetadata->name;
        $classShortName = (new \ReflectionClass($instance->class))->getShortName();
        //$alias = lcfirst($classShortName);
        $instance->fullname = Helper::classToUlr($instance->class) .'.'.$instance->name;
        $instance->title = $instance->name == 'id' ? 'ID' : __(Str::pluralCamelWords( ucfirst($instance->name), 1));

        $fieldMapping = $classMetadata->getFieldMapping($instance->name);
        $length = isset($fieldMapping['length']) ? $fieldMapping['length'] : null;
        $instance->type = $instance->getHtmlType($fieldMapping['type'], $length);
        
        if (array_search($instance->name, FieldContainer::INTERNAL_FIELDS) !== false) {
            $instance->disabled = true;
        }
        
        $requestedOrder = $instance->getRequestedOrder();
        
        if ($requestedOrder && $requestedOrder['field'] == $instance->fullname) {
            $instance->sortable = $requestedOrder['direction'];
        } else {
            $instance->sortable = empty($fieldMapping['length']) || $fieldMapping['length'] <= 255 ? 'true' : 'false';
        }
        
        $instance->textalign = $instance->type == 'number' ? 'right' : 'left';
        $instance->width = $instance->type == 'number' ? 50 : 'auto';
        $instance->autohide = $instance->name != 'id' && !$instance->name != 'name' && !$instance->name != 'code';
        
        return $instance;
    }
    
    public function getHtmlType(string $doctrineType, int $length = null)
    {
        switch ($doctrineType) {
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
                return !$length || $length > 255 ? 'textarea' : 'text';
                break;
        }
        
        return 'text';
    }
    
    public function getRequestedOrder()
    {
        $field = request()->get('order');
        
        if (!$field) {
            return null;
        }
        
        if (is_array($field)) {
            $field = $field[0];
        }
        
        $direction = 'asc';
        
        if (strpos($field, '-') === 0) {
            $direction = 'desc';
            $field = substr($field, 1);
        }
        
        return ['field' => $field, 'direction' => $direction];
    }
    
}
