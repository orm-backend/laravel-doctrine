<?php

namespace OrmBackend\DBAL;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DQLExpression
{
    /**
     * 
     * @var string
     */
    protected $dql;
    
    /**
     *
     * @var string
     */
    protected $name;
    
    /**
     *
     * @var array
     */
    protected $values;
    
    /**
     * 
     * @param string $name
     * @param string $dql
     * @param mixed ...$values
     */
    public function __construct(string $name, string $dql, ... $values)
    {
        $this->name = $name;
        $this->dql = $dql;
        $this->values = $values;
    }

    /**
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    
    /**
     * 
     * @return array
     */    
    public function getValues() : array
    {
        return $this->values;
    }
    
    /**
     * 
     * @param string[] $placeholders
     * @return string
     */
    public function compile(array $placeholders) : string
    {
        return vsprintf($this->dql, $placeholders);
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->dql;
    }
    
}
