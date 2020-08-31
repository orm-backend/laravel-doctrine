<?php

namespace OrmBackend\ORM;

use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Arr;
use OrmBackend\DBAL\DQLExpression;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class QueryValidator
{
    const SUPPORTED = [
        'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'isNull', 'isNotNull', 'like', 'notLike', 'in', 'notIn', 'between'
    ];
    
    /**
     *
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $qb;
    
    /**
     *
     * @var \OrmBackend\ORM\QueryHelper
     */
    protected $helper;
    
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
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \OrmBackend\ORM\QueryHelper $helper
     * @param string $alias
     * @param string $class
     */
    public function __construct(
        QueryBuilder $qb,
        QueryHelper $helper,
        string $alias,
        string $class)
    {
        $this->qb = $qb;
        $this->helper = $helper;
        $this->alias = $alias;
        $this->class = $class;
    }
    
    /**
     *
     * @param string $referenceOrAlias
     * @throws \OrmBackend\ORM\DevelopmentException
     */
    public function validateFieldOrAlias(string $referenceOrAlias)
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $referenceOrAlias);
        
        if ($pieces[0] != $this->alias) {
            $reference = $this->helper->getReferenceByAlias($pieces[0]);
            
            if ($reference) {
                $pieces[0] = $reference;
            } else {
                throw new DevelopmentException("Unknown entity reference '{$pieces[0]}' in '{$referenceOrAlias}'.");
            }
        }
        
        $targetField = null;
        $index = 0;
        
        while ($index < count($pieces) - 1) {
            $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
            $targetField = $pieces[$index + 1];
            $index ++;
            
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $fieldMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $fieldMetadata['targetEntity'];
                continue;
            }
            
            if (!array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Unknown entity filed '{$targetField}'.");
            }
        }
    }
    
    /**
     *
     * @param string $referenceOrAlias
     * @throws \OrmBackend\ORM\DevelopmentException
     */
    public function validateReferenceForSelect(string $referenceOrAlias)
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $referenceOrAlias);
        
        if ($pieces[0] != $this->alias) {
            $reference = $this->helper->getReferenceByAlias($pieces[0]);
            
            if ($reference) {
                $pieces[0] = $reference;
            } else {
                throw new DevelopmentException("Unknown entity reference '{$pieces[0]}' in '{$referenceOrAlias}'.");
            }
        }
        
        $targetField = null;
        $index = 0;
        
        while ($index < count($pieces) - 1) {
            $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
            $targetField = $pieces[$index + 1];
            $index ++;
            
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $referenceMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $referenceMetadata['targetEntity'];
                continue;
            }
            
            if (array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Entity fields cannot be specified in a select.");
            }
            
            throw new DevelopmentException("Unknown entity reference '{$targetField}' in '{$referenceOrAlias}'.");
        }
    }
    
    /**
     * 
     * @param array $comparisonData
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return bool
     */
    public function validateComparisonData(array $comparisonData) : bool
    {
        $fieldOrAlias = null;
        $operator = null;
        $value = null;
        $length = count($comparisonData);
        
        if ($length == 1) {
            [$fieldOrAlias] = $comparisonData;
            
            if (!($fieldOrAlias instanceof DQLExpression)) {
                throw new DevelopmentException("The argument must be instance of OrmBackend\DBAL\DQLExpression when used only one.");
            }
            
            return true;
        } else if ($length == 2) {
            [$fieldOrAlias, $operator] = $comparisonData;
            
            if ($operator != 'isNull' && $operator != 'isNotNull') {
                throw new DevelopmentException("Permitted not to specify a value with operators 'isNull' or 'isNotNull' only.");
            }
        } else if ($length == 3) {
            [$fieldOrAlias, $operator, $value] = $comparisonData;
            
            if ($operator == 'isNull' || $operator == 'isNotNull') {
                throw new DevelopmentException("Not permitted to specify a value with operators 'isNull' or 'isNotNull'.");
            } else if ($operator == 'between') {
                throw new DevelopmentException("Must be a second value with operator 'between'.");
            } else if ($operator == 'in' && (!is_array($value) || Arr::isAssoc($value))) {
                throw new DevelopmentException("The value must be a numeric array with operator 'in'.");
            }
        } else if ($length == 4) {
            [$fieldOrAlias, $operator, $value, $adonceValue] = $comparisonData;
            
            if ($operator != 'between') {
                throw new DevelopmentException("Only operator 'between' permitted to use with two values.");
            }
        } else {
            throw new DevelopmentException('Incompatible filter format.');
        }
        
        
        if (!in_array($operator, self::SUPPORTED)) {
            $supportedOperators = implode(', ', self::SUPPORTED);
            throw new DevelopmentException("Unsupported operator '{$operator}'. Allowed operators: {$supportedOperators}.");
        }
        
        // Skip empty strings and nulls
        if (is_null($value) && $operator != 'isNull' && $operator != 'isNotNull') {
            return false;
        }
        
        return true;
    }
    
}