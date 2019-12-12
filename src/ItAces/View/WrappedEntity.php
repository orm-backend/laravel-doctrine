<?php

namespace ItAces\View;


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
