<?php

namespace ItAces\ORM\Entities;

/**
 * Role
 */
abstract class Role extends \ItAces\ORM\Entities\EntityBase
{
    /**
     * @var int
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $code;

    /**
     * @var string|null
     */
    protected $name;
    
    /**
     *
     * @var integer
     */
    protected $permission;
    
    /**
     * 
     * @var boolean
     */
    protected $system;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return \ItAces\ORM\Entities\Role
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return \ItAces\ORM\Entities\Role
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return integer
     */
    public function getPermission()
    {
        return $this->permission;
    }
    
    /**
     * @param integer $permission
     */
    public function setPermission(int $permission)
    {
        $this->permission = $permission;
    }
    
    /**
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * @param boolean $system
     */
    public function setSystem(bool $system)
    {
        $this->system = $system;
    }

}
