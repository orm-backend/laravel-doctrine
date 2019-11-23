<?php

namespace ItAces\Api;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Expr\Composite;
use Illuminate\Support\Arr;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Query
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
     * @var string
     */
    protected $class;
    
    /**
     * 
     * @var integer
     */
    protected $index = 0;
    
    /**
     * 
     * @var array
     */
    protected $parameters = [];
    
    /**
     * 
     * @var array
     */
    protected $joins = [];

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
     * @var string
     */
    protected $alias;
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $select
     * @param array $filter
     * @return \ItAces\Api\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $select, array $filter) : Query
    {
        $instance = new static();
        $instance->alias = lcfirst( (new \ReflectionClass($class))->getShortName() );
        $instance->qb = $em->createQueryBuilder()->from($class, $instance->alias);
        $instance->select = $select;
        $instance->filter = $filter;
        $instance->class = $class;
        
        return $instance;
    }
    
    /**
     * 
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilder() : QueryBuilder
    {
        dd($this->getFieldType('createdAt'));
        
        foreach ($this->select as $i => $field) {
            $this->select[$field] = $this->fieldToAlias($field);
            unset($this->select[$i]);
            
            if ($field != $this->alias) {
                $this->joins[$field] = $this->select[$field];
            }
        }
        
        // SELECT
        call_user_func_array([$this->qb, 'select'], array_values($this->select));

        // WHERE
        if ($this->filter) {
            if (Arr::isAssoc($this->filter)) {
                $composite = $this->buildCriteria($this->filter);
                $this->qb->where($composite);
            } else {
                $this->qb->where(
                    $this->qb->expr()->andX()->addMultiple(
                        $this->buildComparisons($this->filter)
                    )
                );
            }
        }
        
        // JOIN
        foreach ($this->joins as $join => $alias) {
            $this->qb->leftJoin($join, $alias);
        }
        
        $this->qb->setParameters(new ArrayCollection($this->parameters));
        //dd($this->qb->getQuery()->getDQL());
        
        return $this->qb;
    }
    
    /**
     * 
     * @param string $field
     * @return string
     */
    protected function fieldToAlias(string $field) : string
    {
        return str_replace('.', '_', $field);
    }
    
    /**
     * 
     * @param string $fieldDotedName
     * @return string
     */
    protected function getFieldType(string $fieldDotedName) : string
    {
        $meta = $this->qb->getEntityManager()->getClassMetadata($this->class);
        $pieces = explode('.', $fieldDotedName);
        
        foreach ($pieces as $piece) {
            if (isset($meta->associationMappings[$piece])) {
                
            }
        }
        
        $meta = $this->qb->getEntityManager()->getClassMetadata($this->class);
        dd($meta->associationMappings);
        $fieldMeta = $meta->fieldMappings[$fieldName];
        
        return $fieldMeta['type'];
    }
    
    /**
     * 
     * @param string $column
     * @param int $index
     * @return string[]
     */
    protected function columnToAlias(string $column, int $index) : array
    {
        $position = strrpos($column, '.');
        $field = substr($column, 0, $position);
        $leftExpr = $this->fieldToAlias($field);
        $rightExpr = substr($column, $position + 1);
        
        return [$leftExpr.'.'.$rightExpr, $rightExpr.$index];
    }
    
    /**
     * 
     * @param string $column
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function columnToField(string $column) : string
    {
        $position = strrpos($column, '.');
        
        if (!$position) {
            throw new \InvalidArgumentException("Invalid column name {$column}.");
        }
        
        return substr($column, 0, $position);
    }

    /**
     * 
     * @param array $criteriaData
     * @throws \InvalidArgumentException
     * @return \Doctrine\ORM\Query\Expr\Composite
     */
    protected function buildCriteria(array $criteriaData) : Composite
    {
        if (!$criteriaData || !is_array($criteriaData) || !Arr::isAssoc($criteriaData)) {
            throw new \InvalidArgumentException('Passed argument must be an associative array.');
        }
        
        $composite = $this->qb->expr()->andX();
        $andOr = key($criteriaData);
        $operand = strtolower($andOr);
        
        if ($operand == 'or') {
            $composite = $this->qb->expr()->orX();
        } else if ($operand != 'and') {
            throw new \InvalidArgumentException('The logic operand must be one of: AND, OR.');
        }
        
        $comparisons = $this->buildComparisons($criteriaData[$andOr]);
        
        return $composite->addMultiple($comparisons);
    }
    
    /**
     * 
     * @param array $comparisonsData
     * @throws \InvalidArgumentException
     * @return \Doctrine\ORM\Query\Expr\Comparison[]
     */
    protected function buildComparisons(array $comparisonsData) : array
    {
        if (!$comparisonsData || !is_array($comparisonsData)) {
            throw new \InvalidArgumentException('Passed argument must be an array.');
        }
        
        $comparisons = [];
        
        foreach ($comparisonsData as $criteriaOrComparison) {
            if (Arr::isAssoc($criteriaOrComparison)) {
                $comparisons[] = $this->buildCriteria($criteriaOrComparison);
            } else {
                $comparisons[] = $this->buildComparison($criteriaOrComparison);
            }
        }
        
        return $comparisons;
    }
    
    /**
     * 
     * @param array $comparisonData
     * @throws \InvalidArgumentException
     * @return \Doctrine\ORM\Query\Expr\Comparison|string
     */
    protected function buildComparison(array $comparisonData)
    {
        $column = null;
        $operator = null;
        $value = null;
        $adonce = null;
        $length = count($comparisonData);
        
        if ($length == 2) {
            list($column, $operator) = $comparisonData;
        } else if ($length == 3) {
            list($column, $operator, $value) = $comparisonData;
        } else if ($length == 4) {
            list($column, $operator, $value, $adonce) = $comparisonData;
        } else {
            throw new \InvalidArgumentException('Incompatible filter format.');
        }
        
        
        if (!in_array($operator, self::SUPPORTED)) {
            throw new \InvalidArgumentException('Unsupported operator.');
        }
        
        $field = $this->columnToField($column);
        
        if ($field != $this->alias && !array_key_exists($field, $this->joins)) {
            $this->joins[$field] = $this->fieldToAlias($field);
        }
        
        if ($value === null) {
            list($alias, $parameter) = $this->columnToAlias($column, $this->index);
            
            return call_user_func_array([$this->qb->expr(), $operator], [$alias]);
        }
        
        list($alias, $parameter) = $this->columnToAlias($column, $this->index);
        $this->parameters[] = new Parameter($parameter, $value);
        $this->index ++;
        
        if ($adonce) {
            list($alias, $adonceParameter) = $this->columnToAlias($column, $this->index);
            $this->parameters[] = new Parameter($adonceParameter, $adonce);
            $this->index ++;
            
            return call_user_func_array([$this->qb->expr(), $operator], [$alias, ':'.$parameter, ':'.$adonceParameter]);
        }
        
        return call_user_func_array([$this->qb->expr(), $operator], [$alias, ':'.$parameter]);
    }
    
}
