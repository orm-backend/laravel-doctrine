<?php
namespace OrmBackend\Web\Fields;

use Doctrine\ORM\Mapping\ClassMetadata;
use OrmBackend\ORM\Entities\Entity;
use OrmBackend\Utility\Helper;
use OrmBackend\Utility\Str;

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
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param int $index
     * @return \OrmBackend\Web\Fields\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, Entity $entity = null, int $index = null)
    {
        $instance = new static($classMetadata, $fieldName, $index);
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
     * @param int $index
     */
    protected function __construct(ClassMetadata $classMetadata, string $fieldName, int $index = null)
    {
        $this->name = $fieldName;
        $this->class = $classMetadata->name;
        $this->classUrlName = Helper::classToUrl($this->class);
        $this->aliasname = lcfirst((new \ReflectionClass($this->class))->getShortName()) .'.'. $this->name;
        $this->fullname = Helper::classToUrl($this->class);
        
        /**
         * A form can contain several objects of the same class
         */
        if ($index !== null) {
            $this->fullname .= '[' . $index . ']';
        }
        
        $this->fullname .= '['. $this->name . ']';
        $this->title = __(Str::pluralCamelWords( ucfirst($this->name), 1));
        $this->autohide = $this->name != $classMetadata->getSingleIdentifierFieldName() && !$this->name != 'name' && !$this->name != 'code';
        $internalFields = array_merge(FieldContainer::INTERNAL_FIELDS, [$classMetadata->getSingleIdentifierColumnName()]);
        
        if (in_array($this->name, $internalFields)) {
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
