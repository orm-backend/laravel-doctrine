<?php

namespace ItAces\View;

use ItAces\ORM\Entities\EntityBase;
use ItAces\Types\FileType;
use ItAces\Types\ImageType;


/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class WrappedEntity
{
    /**
     * 
     * @var integer
     */
    protected $id;
    
    /**
     * 
     * @var \ItAces\View\BaseField[]
     */
    protected $fields = [];
    
    /**
     * 
     * @var string
     */
    protected $type;
    
    /**
     * 
     * @param int $id
     */
    public function __construct(EntityBase $entity)
    {
        $this->id = $entity->getId();
        
        if ($entity instanceof ImageType) {
            $this->type = 'image';
        } else if ($entity instanceof FileType) {
            $this->type = 'file';
        } else {
            $this->type = 'common';
        }
    }
    
    /**
     * 
     * @param \ItAces\View\MetaField $field
     */
    public function addField(MetaField $field)
    {
        $this->fields[$field->name] = $field;
    }
    
    /**
     * 
     * @return integer
     */
    public function id()
    {
        return $this->id;
    }
    
    /**
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }
    
    /**
     * 
     * @return \ItAces\View\BaseField[]
     */
    public function fields()
    {
        return $this->fields;
    }
    
    /**
     *
     * @return \ItAces\View\BaseField
     */
    public function field(string $name)
    {
        return $this->fields[$name];
    }

}
