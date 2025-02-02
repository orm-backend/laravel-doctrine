<?php

namespace OrmBackend\ORM;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class QueryHelper
{
    
    /**
     * 
     * @var array
     */
    protected $aliases = [];
    
    /**
     *
     * @var array
     */
    protected $reverse = [];
    
    /**
     *
     * @param string $reference
     * @return string
     */
    public function referenceToAlias(string $reference) : string
    {
        $alias = $this->getAliasByReference($reference);
        
        return $alias ? $alias : str_replace('.', '_', $reference);
    }
    
    /**
     *
     * @param string $field
     * @param bool $withColumn
     * @return string
     */
    public function fieldToAlias(string $field, bool $withColumn = null) : string
    {
        $reference = $this->fieldToReference($field);
        $alias = $this->referenceToAlias($reference);
        
        if (!$withColumn) {
            return $alias;
        }
        
        $column = $this->fieldToColumn($field);
        
        return $alias.'.'.$column;
    }
    
    /**
     *
     * @param string $field
     * @param int $index
     * @return string
     */
    public function fieldToPlaceholderName(string $field, int $index) : string
    {
        $column = $this->fieldToColumn($field);
        
        return $column.$index;
    }
    
    /**
     *
     * @param string $field
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return string
     */
    public function fieldToReference(string $field) : string
    {
        $position = strrpos($field, '.');
        
        if (!$position) {
            throw new DevelopmentException("Invalid field name '{$field}'.");
        }
        
        return substr($field, 0, $position);
    }
    
    /**
     *
     * @param string $field
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return string
     */
    public function fieldToColumn(string $field) : string
    {
        $position = strrpos($field, '.');
        
        if (!$position) {
            throw new DevelopmentException("Invalid field name '{$field}'.");
        }
        
        return substr($field, $position + 1);
    }
    
    /**
     * 
     * @param string $alias
     * @return string|NULL
     */
    public function getReferenceByAlias(string $alias)
    {
        if (!array_key_exists($alias, $this->reverse)) {
            return null;
        }

        return str_replace('_', '.', $alias);
    }
    
    /**
     * 
     * @param string $reference
     * @return string|NULL
     */
    public function getAliasByReference(string $reference)
    {
        if (array_key_exists($reference, $this->aliases)) {
            return $this->aliases[$reference];
        }
        
        return null;
    }
    
    /**
     * 
     * @param string $reference
     * @param string $alias
     */
    public function addAlias(string $reference, string $alias)
    {
        // TODO: Should I allow overwriting them?
        $this->aliases[$reference] = $alias;
        $this->reverse[$alias] = $reference;
    }
    
    /**
     * 
     * @param string $referenceOrAlias
     * @return boolean
     */
    public function isAlias(string $referenceOrAlias)
    {
        return strpos($referenceOrAlias, '.') === false && array_key_exists($referenceOrAlias, $this->reverse);
    }
    
}
