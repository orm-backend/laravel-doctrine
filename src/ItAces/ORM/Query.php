<?php

namespace ItAces\ORM;

use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\OrderBy;
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
     * @var boolean
     */
    protected $useNamedParameters = true;
    
    /**
     *
     * @var boolean
     */
    protected $useStrongTyping = true;
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $parameters
     * @return \ItAces\ORM\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $parameters) : Query
    {
        $instance = new static();
        $instance->alias = lcfirst( (new \ReflectionClass($class))->getShortName() );
        $instance->qb = $em->createQueryBuilder()->from($class, $instance->alias);
        $instance->select = array_key_exists('select', $parameters) ? $parameters['select'] : [];
        $instance->filter = array_key_exists('filter', $parameters) ? $parameters['filter'] : [];
        $instance->order = array_key_exists('order', $parameters) ? $parameters['order'] : [];
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
            $this->validateFieldForSelect($field);
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
            $composite = $this->buildCriteria($this->filter);
            $this->qb->where($composite);
        }
        
        // JOIN
        foreach ($this->joins as $join => $alias) {
            $this->qb->leftJoin($join, $alias);
        }

        foreach ($this->parameters as $parameter) {
            $this->qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }
        
        foreach ($this->order as $column) {
            $this->validateFieldForOrder($column);
            $field = $this->columnToAlias($column);
            $order = $this->buildOrder($field);
            $this->qb->addOrderBy($order);
        }
        
        //dd($this->qb->getQuery()->getDQL(), end($this->parameters));
        
        return $this->qb;
    }
    
    /**
     * 
     * @param string $field
     * @return OrderBy
     */
    protected function buildOrder(string $field) : OrderBy
    {
        $operator = 'asc';
        
        if (strpos($field, '-') === 0) {
            $operator = 'desc';
            $field = substr($field, 1);
        }
        
        return call_user_func_array([$this->qb->expr(), $operator], [$field]);
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
     * @throws \ItAces\ORM\DevelopmentException
     */
    protected function validateFieldForOrder(string $fieldDotedName)
    {
        if (strpos($fieldDotedName, '-') === 0) {
            $fieldDotedName = substr($fieldDotedName, 1);
        }
        
        $targetEntity = $this->class;
        $pieces = explode('.', $fieldDotedName);
        
        if ($pieces[0] != $this->alias) {
            throw new DevelopmentException("Unknown entity alias '{$pieces[0]}' in '{$fieldDotedName}'.");
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
                throw new DevelopmentException("Unknown entity filed '{$targetField}' in order '{$fieldDotedName}'.");
            }
        }
    }
    
    /**
     * 
     * @param string $fieldDotedName
     * @throws \ItAces\ORM\DevelopmentException
     */
    protected function validateFieldForSelect(string $fieldDotedName)
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $fieldDotedName);
        
        if ($pieces[0] != $this->alias) {
            throw new DevelopmentException("Unknown entity alias '{$pieces[0]}' in '{$fieldDotedName}'.");
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
            
            if (array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Separate entity fields cannot be specified in a select.");
            }
            
            throw new DevelopmentException("Unknown entity reference '{$targetField}' in '{$fieldDotedName}'.");
        }
    }
    
    /**
     *
     * @param string $fieldDotedName
     * @throws \ItAces\ORM\DevelopmentException
     * @return string
     */
    protected function getFieldType(string $fieldDotedName) : string
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $fieldDotedName);
        
        if (count($pieces) < 2) {
            throw new DevelopmentException("The passed field '{$fieldDotedName}' name must contain a dot.");
        }
        
        if ($pieces[0] != $this->alias) {
            throw new DevelopmentException("Unknown entity alias '{$pieces[0]}' in '{$fieldDotedName}'.");
        }
        
        $targetField = null;
        $index = 0;
        
        while ($index < count($pieces) - 1) {
            $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
            $targetField = $pieces[$index + 1];
            
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $fieldMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $fieldMetadata['targetEntity'];
            } else if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Unknown reference '{$targetField}' in '{$fieldDotedName}'.");
            }
            
            $index ++;
        }
        
        $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
        
        if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
            throw new DevelopmentException("Unknown field '{$targetField}' in '{$fieldDotedName}'.");
        }
        
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
     * @throws \ItAces\ORM\DevelopmentException
     * @return string
     */
    protected function columnToField(string $column) : string
    {
        $position = strrpos($column, '.');
        
        if (!$position) {
            throw new DevelopmentException("Invalid column name '{$column}'.");
        }
        
        return substr($column, 0, $position);
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
        $column = null;
        $operator = null;
        $value = null;
        $adonceValue = null;
        $length = count($comparisonData);
        
        if ($length == 2) {
            list($column, $operator) = $comparisonData;
            
            if ($operator != 'isNull' && $operator != 'isNotNull') {
                throw new DevelopmentException("Permitted not to specify a value with operators 'isNull' or 'isNotNull' only.");
            }
        } else if ($length == 3) {
            list($column, $operator, $value) = $comparisonData;
            
            if ($operator == 'isNull' || $operator == 'isNotNull') {
                throw new DevelopmentException("Not permitted to specify a value with operators 'isNull' or 'isNotNull'.");
            } else if ($operator == 'between') {
                throw new DevelopmentException("Must be a second value with operator 'between'.");
            } else if ($operator == 'in' && (!is_array($value) || Arr::isAssoc($value))) {
                throw new DevelopmentException("The value must be a numeric array with operator 'in'.");
            }
        } else if ($length == 4) {
            list($column, $operator, $value, $adonceValue) = $comparisonData;
            
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
     * @param string|array $value
     * @return string
     */
    protected function buildQueryParameterName(string $column, $value) : string
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
     * @param string $value|array
     * @return Parameter
     */
    protected function buildQueryParameter(string $column, $name, $value) : Parameter
    {
        if (is_array($value)) {
            if (!$value) {
                throw new DevelopmentException("The value for field '{$column}' could not be an empty array.");
            }
            
            $integerTypes = [Types::INTEGER, Types::SMALLINT, Types::BIGINT];
            $stringTypes = [Types::STRING];
            $mappingTypes = implode(', ', array_merge($integerTypes, $stringTypes));
            $valueTypes = implode(', ', [Types::INTEGER, Types::STRING]);
            $fieldType = $this->getFieldType($column);
            $connectionType = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
            
            switch ($fieldType) {
                case Types::BIGINT:
                case Types::INTEGER:
                case Types::SMALLINT:
                    break;
                case Types::STRING:
                    $connectionType = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                    break;
                default:
                    throw new DevelopmentException("Unsupported type found for field '{$column}'. It is allowed to use the IN operator only for types: '{$supportedTypes}'.");
                    break;
            }
            
            array_map(function($element) use($integerTypes, $stringTypes, $column, $fieldType, $valueTypes) {
                if ((in_array($element, $integerTypes) && !is_int($element)) || (in_array($element, $stringTypes) && !is_string($element))) {
                    $wrongType = gettype($element);
                    throw new DevelopmentException("Found the array element with type '{$wrongType}' that does not match type '{$fieldType}' for field '{$column}'. Valid types for array elements are: '{$valueTypes}'.");
                }
            }, $value);
            
            return new Parameter($name, $value,  $connectionType);
        }
        
        if ($this->useStrongTyping) {
            //dd( \Doctrine\DBAL\Types\Type::getTypesMap());
            $type = $this->getFieldType($column);
            
            switch ($type) {
                case Types::BIGINT:
                case Types::INTEGER:
                case Types::SMALLINT:
                    $value = (int) $value;
                    break;
                case Types::FLOAT:
                case Types::DECIMAL:
                    $value = (float) $value;
                    break;
                case Types::BOOLEAN:
                    $value = (boolean) $value;
                    break;
                case Types::DATE_MUTABLE:
                case Types::DATETIME_MUTABLE:
                case Types::DATETIMETZ_MUTABLE:
                //case Types::TIME_MUTABLE: TODO
                    $timeZone = null;
                    
                    if (auth()->id() && method_exists(auth()->user(), 'getTimezone')) {
                        $timeZone = auth()->user()->getTimezone();
                    }
                    
                    $value = Carbon::parse($value, $timeZone);
                    break;
            }
        }
        
        return new Parameter($name, $value, $type);
    }
    
}
