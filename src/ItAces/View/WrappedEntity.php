<?php

namespace ItAces\View;

use ItAces\View\BaseField;

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
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }
    
    /**
     * 
     * @param \ItAces\View\BaseField $field
     */
    public function addField(BaseField $field)
    {
        $this->fields[] = $field;
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
     * @return \ItAces\View\BaseField[]
     */
    public function fields()
    {
        return $this->fields;
    }

}
