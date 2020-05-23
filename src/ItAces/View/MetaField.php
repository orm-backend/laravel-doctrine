<?php

namespace ItAces\View;

use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
abstract class MetaField
{
    /**
     * 
     * @var mixed
     */
    public $value;
    
    /**
     * 
     * @var string
     */
    public $class;
    
    /**
     *
     * @var string
     */
    public $name;
    
    /**
     *
     * @var string
     */
    public $fullname;
    
    /**
     *
     * @var string
     */
    public $aliasname;
    
    /**
     *
     * @var string
     */
    public $title;
    
    /**
     *
     * @var string
     */
    public $type;
    
    /**
     *
     * @var string
     */
    public $sortable;
    
    /**
     *
     * @var string
     */
    public $textalign;
    
    /**
     *
     * @var string|integer
     */
    public $width;
    
    /**
     * 
     * @var boolean
     */
    public $autohide;
    
    /**
     * 
     * @var boolean
     */
    public $disabled = false;
    
    /**
     * 
     * @var string
     */
    public $classUrlName;
    
    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @return \ItAces\View\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, EntityBase $entity = null)
    {
        $instance = new static($classMetadata, $fieldName);
        $instance->type = $instance->getHtmlType();
        $instance->textalign = $instance->type == 'number' ? 'right' : 'left';
        $instance->width = $instance->type == 'number' ? 50 : 'auto';
        $requestedOrder = $instance->getRequestedOrder();
        
        if ($requestedOrder && $requestedOrder['field'] == $instance->aliasname) {
            $instance->sortable = $requestedOrder['direction'];
        } else {
            $instance->sortable = $instance->getDefaultSortable();
        }

        return $instance;
    }
    
    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     */
    protected function __construct(ClassMetadata $classMetadata, string $fieldName)
    {
        $this->name = $fieldName;
        $this->class = $classMetadata->name;
        $this->classUrlName = Helper::classToUlr($this->class);
        $this->aliasname = lcfirst((new \ReflectionClass($this->class))->getShortName()) .'.'. $this->name;
        $this->fullname = Helper::classToUlr($this->class) .'['. $this->name . ']';
        $this->title = $this->name == 'id' ? 'ID' : __(Str::pluralCamelWords( ucfirst($this->name), 1));
        $this->autohide = $this->name != 'id' && !$this->name != 'name' && !$this->name != 'code';
        
        if (array_search($this->name, FieldContainer::INTERNAL_FIELDS) !== false) {
            $this->disabled = true;
        }
    }
    
    /**
     * 
     * @return string
     */
    protected abstract function getHtmlType();
    
    /**
     *
     * @return string
     */
    protected abstract function getDefaultSortable();
    
    /**
     * 
     * @return NULL|string[]
     */
    protected function getRequestedOrder()
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
