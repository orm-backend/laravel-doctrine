<?php

namespace ItAces\ORM;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\OrderBy;
use Illuminate\Support\Arr;
use ItAces\DBAL\DQLExpression;

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
     * @var \ItAces\ORM\QueryHelper
     */
    protected $helper;
    
    /**
     * 
     * @var \ItAces\ORM\QueryValidator
     */
    protected $validator;
    
    /**
     * 
     * @var \ItAces\ORM\ParameterBuilder
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
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return \ItAces\ORM\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $parameters = [], string $alias = null) : Query
    {
        $instance = new static();
        $instance->alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
        $instance->qb = $em->createQueryBuilder()->from($class, $instance->alias);
        $instance->select = array_key_exists('select', $parameters) ? $parameters['select'] : [];
        $instance->join = array_key_exists('join', $parameters) ? $parameters['join'] : [];
        $instance->filter = array_key_exists('filter', $parameters) ? $parameters['filter'] : [];
        $instance->order = array_key_exists('order', $parameters) ? $parameters['order'] : [];
        
        if (!in_array($instance->alias, $instance->select)) {
            $instance->select[] = $instance->alias;
        }
        
        $instance->helper = new QueryHelper();
        $instance->validator = new QueryValidator($instance->qb, $instance->helper, $instance->alias, $class);
        $instance->builder = new ParameterBuilder($instance->qb, $instance->helper, $instance->alias, $class);
        
        return $instance;
    }
    
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
            $this->addJoinIfNeed($reference, $alias);
        }
        
        // WHERE
        if ($this->filter) {
            $composite = $this->buildCriteria($this->filter);
            $this->qb->where($composite);
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
            return;
        }
        
        if ($referenceOrAlias != $this->alias && !array_key_exists($referenceOrAlias, $this->join)) {
            $this->join[$referenceOrAlias] = $alias;
        }
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
        $reference = $this->helper->fieldToReference($field);
        $alias = $this->helper->referenceToAlias($reference);
        $this->addJoinIfNeed($reference, $alias);
        $alias = $this->helper->fieldToAlias($field);
        
        return call_user_func_array([$this->qb->expr(), $direction], [$alias]);
    }
    
    /**
     *
     * @param array $criteriaData
     * @throws \ItAces\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Expr\Composite
     */
    protected function buildCriteria(array $criteriaData) : Composite
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
     * @throws \ItAces\ORM\DevelopmentException
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
                $comparisons[] = $this->buildCriteria($comparisonOrCriteria);
            } else {
                $comparisons[] = $this->buildComparison($comparisonOrCriteria);
            }
        }
        
        return $comparisons;
    }
    
    /**
     *
     * @param array $comparisonData
     * @throws \ItAces\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Expr\Comparison|string
     */
    protected function buildComparison(array $comparisonData)
    {
        $field = null;
        $operator = null;
        $value = null;
        $adonceValue = null;
        
        $this->validator->validateComparisonData($comparisonData);
        $length = count($comparisonData);

        if ($length == 1) {
            /**
             *
             * @var \ItAces\DBAL\DQLExpression $expression
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
            $alias = $this->helper->fieldToAlias($field);
            $this->addJoinIfNeed($referenceOrAlias, $alias);
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
     * @param \ItAces\ORM\QueryHelper $helper
     */
    public function setHelper(QueryHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param \ItAces\ORM\QueryValidator $validator
     */
    public function setValidator(QueryValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \ItAces\ORM\ParameterBuilder $builder
     */
    public function setBuilder(ParameterBuilder $builder)
    {
        $this->builder = $builder;
    }

}
