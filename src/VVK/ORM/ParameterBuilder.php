<?php

namespace VVK\ORM;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;
use VVK\DBAL\DQLExpression;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ParameterBuilder
{
    /**
     *
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $qb;
    
    /**
     * 
     * @var \VVK\ORM\QueryHelper
     */
    protected $helper;
    
    /**
     *
     * @var \VVK\ORM\Orderly
     */
    protected $orderly;
    
    /**
     * 
     * @var integer
     */
    protected $index = 0;
    
    /**
     * 
     * @var \Doctrine\ORM\Query\Parameter[]
     */
    protected $parameters = [];
    
    /**
     *
     * @var string
     */
    protected $class;
    
    /**
     * 
     * @var string
     */
    protected $alias;
    
    /**
     *
     * @var boolean
     */
    protected $useNamedParameters = true;
    
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \VVK\ORM\QueryHelper $helper
     * @param string $alias
     * @param string $class
     * @param bool $useNamedParameters
     */
    public function __construct(
        QueryBuilder $qb,
        QueryHelper $helper,
        string $alias,
        string $class,
        bool $useNamedParameters = null)
    {
        $this->qb = $qb;
        $this->helper = $helper;
        $this->alias = $alias;
        $this->class = $class;
        $this->useNamedParameters = $useNamedParameters !== false;
        $this->orderly = new Orderly();
    }
    
    /**
     * 
     * @param DQLExpression $expression
     * @return string
     */
    public function createParameterByExpression(DQLExpression $expression) : string
    {
        $placeholders = [];
        
        foreach ($expression->getValues() as $value) {
            $this->index ++;
            $parameterName = $this->useNamedParameters ? $expression->getName() . $this->index : $this->index;
            $placeholders[] = ($this->useNamedParameters ? ':' : '?') . $parameterName;
            $this->parameters[] = new Parameter( $parameterName, $value );
        }
        
        return $expression->compile($placeholders);
    }
    
    /**
     *
     * @param string $field
     * @param string|array $value
     * @return string
     */
    public function createParameter(string $field, $value) : string
    {
        $this->index ++;
        $placeholder = $this->index;
        
        if ($this->useNamedParameters) {
            $placeholder = $this->helper->fieldToPlaceholderName($field, $this->index);
        }
        
        $this->parameters[] = is_array($value) ? $this->createParameterByArray($field, $placeholder, $value) : $this->createParameterByString($field, $placeholder, $value);
        
        return ($this->useNamedParameters ? ':' : '?') . $placeholder;
    }
    
    /**
     *
     * @param string $field
     * @param integer|string $placeholder
     * @param array $value
     * @throws \VVK\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Parameter
     */
    protected function createParameterByArray(string $field, $placeholder, array $value) : Parameter
    {
        if (!$value) {
            throw new DevelopmentException("The value for field '{$field}' could not be an empty array.");
        }
        
        $fieldMetadata = $this->getFieldMetadata($field);
        [$value, $type] = $this->orderly->sanitizeArray($fieldMetadata, $value);

        return new Parameter($placeholder, $value,  $type);
    }
    
    /**
     *
     * @param string $field
     * @param integer|string $placeholder
     * @param string $value
     * @throws \VVK\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Parameter
     */
    protected function createParameterByString(string $field, $placeholder, string $value) : Parameter
    {
        $type = null;
        $fieldMetadata = $this->getFieldMetadata($field);
        $value = $this->orderly->sanitizeString($fieldMetadata, $value);

        return new Parameter($placeholder, $value, $type);
    }

    /**
     *
     * @param string $referenceOrAlias
     * @throws \VVK\ORM\DevelopmentException
     * @return array
     */
    protected function getFieldMetadata(string $referenceOrAlias) : array
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $referenceOrAlias);
        
        if (count($pieces) < 2) {
            throw new DevelopmentException("The passed field '{$referenceOrAlias}' name must contain a dot.");
        }
        
        if ($this->helper->isAlias($pieces[0])) {
            $reference = $this->helper->getReferenceByAlias($pieces[0]);
            
            if ($reference) {
                $column = $pieces[1];
                $pieces = explode('.', $reference);
                $pieces[] = $column;
            } else {
                throw new DevelopmentException("Unknown entity reference '{$pieces[0]}' in '{$referenceOrAlias}'.");
            }
        }
        
        $targetField = null;
        $targetLength = count($pieces) - 2;
        $index = 0;
        
        while ($index <= $targetLength) {
            $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
            $targetField = $pieces[$index + 1];
 
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $fieldMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $fieldMetadata['targetEntity'];
            } else if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Unknown reference '{$targetField}' in '{$referenceOrAlias}'.");
            }
            
            $index ++;
        }

        $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);

        if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
            throw new DevelopmentException("Unknown field '{$targetField}' in '{$referenceOrAlias}'.");
        }
        
        return $classMetadata->fieldMappings[$targetField];
    }
    
    /**
     * 
     * @return \Doctrine\ORM\Query\Parameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    
}
