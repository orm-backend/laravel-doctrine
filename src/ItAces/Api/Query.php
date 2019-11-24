<?php

namespace ItAces\Api;

use Carbon\Carbon;
use Doctrine\DBAL\ParameterType;
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
     * @var \Doctrine\ORM\Query\Parameter[]
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
     * @var boolean
     */
    protected $useNamedParameters = true;
    
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

        foreach ($this->parameters as $parameter) {
            $this->qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }
        
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
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getFieldType(string $fieldDotedName) : string
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $fieldDotedName);
        
        if (count($pieces) < 2) {
            throw new \InvalidArgumentException('The passed field name must contain a dot.');
        }
        
        $targetField = null;
        $index = 0;
        
        while ($index < count($pieces) - 1) {
            $targetField = $pieces[$index + 1];
            $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
            
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $fieldMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $fieldMetadata['targetEntity'];
            }
            
            $index ++;
        }
        
        $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
        $fieldMetadata = $classMetadata->fieldMappings[$targetField];
        
        return $fieldMetadata['type'];
    }
    
    /**
     *
     * @param string $column
     * @return string
     */
    protected function columnToAlias(string $column) : string
    {
        $position = strrpos($column, '.');
        $field = substr($column, 0, $position);
        $leftExpr = $this->fieldToAlias($field);
        $rightExpr = substr($column, $position + 1);
        
        return $leftExpr.'.'.$rightExpr;
    }
    
    /**
     *
     * @param string $column
     * @return string
     */
    protected function columnToParameter(string $column) : string
    {
        $position = strrpos($column, '.');
        $field = substr($column, 0, $position);
        $leftExpr = $this->fieldToAlias($field);
        $rightExpr = substr($column, $position + 1);
        
        return $rightExpr.$this->index;
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
            throw new \InvalidArgumentException('The passed argument must be an associative array.');
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
            throw new \InvalidArgumentException('The passed argument must be an array.');
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
        $adonceValue = null;
        $length = count($comparisonData);
        
        if ($length == 2) {
            list($column, $operator) = $comparisonData;
        } else if ($length == 3) {
            list($column, $operator, $value) = $comparisonData;
        } else if ($length == 4) {
            list($column, $operator, $value, $adonceValue) = $comparisonData;
        } else {
            throw new \InvalidArgumentException('Incompatible filter format.');
        }
        
        
        if (!in_array($operator, self::SUPPORTED)) {
            throw new \InvalidArgumentException('Unsupported operator.');
        }
        
        $field = $this->columnToField($column);
        $alias = $this->columnToAlias($column);
        
        if ($field != $this->alias && !array_key_exists($field, $this->joins)) {
            $this->joins[$field] = $this->fieldToAlias($field);
        }
        
        if ($value === null) {
            return call_user_func_array([$this->qb->expr(), $operator], [$alias]);
        }

        $parameterName = $this->buildQueryParameterName($column, $value);
        
        if ($adonceValue) {
            $adonceParameterName = $this->buildQueryParameterName($column, $adonceValue);
            
            return call_user_func_array([$this->qb->expr(), $operator], [$alias, $parameterName, $adonceParameterName]);
        }
        
        return call_user_func_array([$this->qb->expr(), $operator], [$alias, $parameterName]);
    }
    
    /**
     * 
     * @param string $column
     * @param string $value
     * @return string
     */
    protected function buildQueryParameterName(string $column, string $value) : string
    {
        $this->index ++;
        $parameter = $this->index;
        
        if ($this->useNamedParameters) {
            $parameter = $this->columnToParameter($column);
        }

        $this->parameters[] = $this->buildQueryParameter($column, $parameter, $value);
        
        return ($this->useNamedParameters ? ':' : '?') . $parameter;
    }
    
    /**
     *
     * @param string $column
     * @param integer|string $name
     * @param string $value
     * @return Parameter
     */
    protected function buildQueryParameter(string $column, $name, string $value) : Parameter
    {
        //dd( \Doctrine\DBAL\Types\Type::getTypesMap());
        $type = $this->getFieldType($column);
        $parameterType = ParameterType::STRING;
        
        switch ($type) {
            case 'integer':
            case 'smallint':
            case 'time':
                $value = (int) $value;
                $parameterType = ParameterType::INTEGER;
                break;
            case 'float':
                $value = (float) $value;
                break;
            case 'boolean':
                $value = (boolean) $value;
                $parameterType = ParameterType::BOOLEAN;
                break;
            case 'date':
            case 'datetime':
                $timeZone = null;
                
                if (auth()->user() && method_exists(auth()->user(), 'getTimezone')) {
                    $timeZone = auth()->user()->getTimezone();
                }
                
                $value = Carbon::parse($value, $timeZone);
                break;
        }
        
        return new Parameter($name, $value, $parameterType);
    }
    
}
