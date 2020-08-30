<?php

namespace VVK\ORM;


use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\OrderBy;
use Illuminate\Support\Arr;
use VVK\DBAL\DQLExpression;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Query
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
     * @var \VVK\ORM\QueryValidator
     */
    protected $validator;
    
    /**
     * 
     * @var \VVK\ORM\ParameterBuilder
     */
    protected $builder;
    
    /**
     *
     * @var \Doctrine\ORM\Query\Parameter[]
     */
    protected $parameters = [];
    
    /**
     *
     * @var array
     */
    protected $join;
    
    /**
     *
     * @var array
     */
    protected $select;
    
    /**
     *
     * @var array
     */
    protected $filter;
    
    /**
     *
     * @var array
     */
    protected $order;
    
    /**
     *
     * @var string
     */
    protected $alias;
    
    /**
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilder() : QueryBuilder
    {
        $this->join = $this->buildAliasesFor($this->join);
        $this->select = $this->buildAliasesFor($this->select);
        
        // SELECT
        $this->qb->select($this->alias);
        
        foreach ($this->select as $reference => $alias) {
            $this->qb->addSelect($alias);
            $this->buildJoins($reference, true);
        }
        
        // WHERE
        if ($this->filter) {
            $criteria = $this->buildCriteria($this->filter);
            
            if ($criteria) {
                $this->qb->where($criteria);
            }
        }
        
        // ORDER
        foreach ($this->order as $field) {
            if ($field instanceof DQLExpression) {
                $order = $this->builder->createParameterByExpression($field);
                $this->qb->add('orderBy', $order, true);
                continue;
            }
            
            $order = $this->buildOrder($field);
            $this->qb->addOrderBy($order);
        }
        
        // JOIN
        foreach ($this->join as $reference => $alias) {
            $this->qb->leftJoin($reference, $alias);
        }

        foreach ($this->builder->getParameters() as $parameter) {
            $this->qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }

        //dd($this->qb->getQuery()->getDQL(), end($this->parameters));
        
        return $this->qb;
    }
    
    /**
     * 
     * @param string $referenceOrAlias
     * @param string $alias
     */
    protected function addJoinIfNeed(string $referenceOrAlias, string $alias)
    {
        if ($this->helper->isAlias($referenceOrAlias)) {
            return false;
        }
        
        if ($referenceOrAlias != $this->alias && !array_key_exists($referenceOrAlias, $this->join)) {
            $this->join[$referenceOrAlias] = $alias;
            $this->helper->addAlias($referenceOrAlias, $alias);
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * @param mixed[] $values
     * @return mixed[]
     */
    protected function buildAliasesFor(array $values)
    {
        $result = [];
        
        foreach ($values as $key => $value) {
            if ($value == $this->alias) {
                continue;
            }
            
            if (is_numeric($key)) {
                $reference = $value;
                $alias = $this->helper->referenceToAlias($reference);
            } else {
                $reference = $key;
                $alias = $value; // explicitly specified
            }
            
            $result[$reference] = $alias;
            $this->helper->addAlias($reference, $alias);
            $this->validator->validateReferenceForSelect($reference);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param string $field
     * @return OrderBy
     */
    protected function buildOrder(string $field) : OrderBy
    {
        $direction = 'asc';
        
        if (strpos($field, '-') === 0) {
            $direction = 'desc';
            $field = substr($field, 1);
        }
        
        $this->validator->validateFieldOrAlias($field);
        $alias = $this->buildJoins($field);
        
        return call_user_func_array([$this->qb->expr(), $direction], [$alias]);
    }
    
    /**
     *
     * @param array $criteriaData
     * @throws \VVK\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Expr\Composite|boolean
     */
    protected function buildCriteria(array $criteriaData)
    {
        if (!$criteriaData || Arr::isAssoc($criteriaData)) {
            throw new DevelopmentException('The passed argument must be a numeric array.');
        }
        
        $composite = $this->qb->expr()->andX();
        
        if (is_string($criteriaData[0])) {
            if (strtolower($criteriaData[0]) == 'or') {
                $composite = $this->qb->expr()->orX();
            }
            
            array_shift($criteriaData);
        }
        
        $comparisons = $this->buildComparisons($criteriaData);
        
        if (!$comparisons) {
            return false;
        }
        
        return $composite->addMultiple($comparisons);
    }
    
    /**
     * 
     * @param array $comparisonOrCriteria
     * @return bool
     */
    protected function isCriteria(array $comparisonOrCriteria) : bool
    {
        if (is_string($comparisonOrCriteria[0])) {
            $operand = strtolower($comparisonOrCriteria[0]);
            
            if ($operand == 'or' || $operand == 'and') {
                return true;
            }
        }
        
        return is_array($comparisonOrCriteria[0]);
    }
    
    /**
     *
     * @param array $comparisonsData
     * @throws \VVK\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Expr\Comparison[]
     */
    protected function buildComparisons(array $comparisonsData) : array
    {
        if (!$comparisonsData) {
            throw new DevelopmentException('The passed argument must be an array.');
        }
        
        $comparisons = [];
        
        foreach ($comparisonsData as $comparisonOrCriteria) {
            if ($this->isCriteria($comparisonOrCriteria)) {
                $comparison = $this->buildCriteria($comparisonOrCriteria);
                
                if (!$comparison) {
                    continue;
                }
                
                $comparisons[] = $comparison;
            } else {
                $comparison = $this->buildComparison($comparisonOrCriteria);
                
                if (!$comparison) {
                    continue;
                }
                
                $comparisons[] = $comparison;
            }
        }

        return $comparisons;
    }
    
    /**
     *
     * @param array $comparisonData
     * @throws \VVK\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Expr\Comparison|string|boolean
     */
    protected function buildComparison(array $comparisonData)
    {
        $field = null;
        $operator = null;
        $value = null;
        $adonceValue = null;
        
        if (!$this->validator->validateComparisonData($comparisonData)) {
            return false;
        }
        
        $length = count($comparisonData);

        if ($length == 1) {
            /**
             *
             * @var \VVK\DBAL\DQLExpression $expression
             */
            [$expression] = $comparisonData;

            return $this->builder->createParameterByExpression($expression);
        } else if ($length == 2) {
            [$field, $operator] = $comparisonData;
        } else if ($length == 3) {
            [$field, $operator, $value] = $comparisonData;
        } else if ($length == 4) {
            [$field, $operator, $value, $adonceValue] = $comparisonData;
        }
        
        $referenceOrAlias = $this->helper->fieldToReference($field);
        
        if (!$this->helper->isAlias($referenceOrAlias)) {
            $field = $this->buildJoins($field);
        }

        if ($value === null) {
            return call_user_func_array([$this->qb->expr(), $operator], [$field]);
        }
        
        $parameterName = $this->builder->createParameter($field, $value);

        if ($adonceValue) {
            $adonceParameterName = $this->builder->createParameter($field, $adonceValue);
            
            return call_user_func_array([$this->qb->expr(), $operator], [$field, $parameterName, $adonceParameterName]);
        }
        
        return call_user_func_array([$this->qb->expr(), $operator], [$field, $parameterName]);
    }
    
    /**
     * 
     * @param string $field
     * @param bool $skipColumn
     * @return string
     */
    protected function buildJoins(string $field, bool $skipColumn = null) : string
    {
        if ($skipColumn) {
            $column = null;
            $pieces = explode('.', $field);
        } else {
            $column = $this->helper->fieldToColumn($field);
            $pieces = explode('.', $this->helper->fieldToReference($field));
        }
        
        $alias = $pieces[0];
        
        for ($i = 0; $i < count($pieces) - 1; $i++) {
            $reference = $alias . '.' . $pieces[$i + 1];
            $alias = $alias . '_' . $pieces[$i + 1];
            $this->addJoinIfNeed($reference, $alias);
        }
        
        return $skipColumn ? $alias : $alias.'.'.$column;
    }
    
    /**
     * @param array $join
     */
    public function setJoin(array $join)
    {
        $this->join = $join;
    }

    /**
     * @param array $select
     */
    public function setSelect(array $select)
    {
        $this->select = $select;
    }

    /**
     * @param array $filter
     */
    public function setFilter(array $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param array $order
     */
    public function setOrder(array $order)
    {
        $this->order = $order;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }
    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @param \VVK\ORM\QueryHelper $helper
     */
    public function setHelper(QueryHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param \VVK\ORM\QueryValidator $validator
     */
    public function setValidator(QueryValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \VVK\ORM\ParameterBuilder $builder
     */
    public function setBuilder(ParameterBuilder $builder)
    {
        $this->builder = $builder;
    }

}
