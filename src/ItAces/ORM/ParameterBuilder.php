<?php

namespace ItAces\ORM;

use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;

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
     * @var \ItAces\ORM\QueryHelper
     */
    protected $helper;
    
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
    protected $useStrongTyping = true;
    
    /**
     *
     * @var boolean
     */
    protected $useNamedParameters = true;
    
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \ItAces\ORM\QueryHelper $helper
     * @param string $alias
     * @param string $class
     * @param bool $useNamedParameters
     * @param bool $useStrongTyping
     */
    public function __construct(
        QueryBuilder $qb,
        QueryHelper $helper,
        string $alias,
        string $class,
        bool $useNamedParameters = null,
        bool $useStrongTyping = null)
    {
        $this->qb = $qb;
        $this->helper = $helper;
        $this->alias = $alias;
        $this->class = $class;
        $this->useNamedParameters = $useNamedParameters !== false;
        $this->useStrongTyping = $useStrongTyping !== false;
    }
    
    /**
     *
     * @param string $field
     * @param string|array $value
     * @return string
     */
    public function buildQueryParameter(string $field, $value) : string
    {
        $this->index ++;
        $placeholder = $this->index;
        
        if ($this->useNamedParameters) {
            $placeholder = $this->helper->fieldToPlaceholderName($field, $this->index);
        }
        
        $this->parameters[] = $this->buildParameter($field, $placeholder, $value);
        
        return ($this->useNamedParameters ? ':' : '?') . $placeholder;
    }
    
    /**
     *
     * @param string $field
     * @param integer|string $name
     * @param string $value|array
     * @throws \ItAces\ORM\DevelopmentException
     * @return \Doctrine\ORM\Query\Parameter
     */
    protected function buildParameter(string $field, $name, $value) : Parameter
    {
        if (is_array($value)) {
            if (!$value) {
                throw new DevelopmentException("The value for field '{$field}' could not be an empty array.");
            }
            
            $integerTypes = [Types::INTEGER, Types::SMALLINT, Types::BIGINT];
            $stringTypes = [Types::STRING];
            $mappingTypes = implode(', ', array_merge($integerTypes, $stringTypes));
            $valueTypes = implode(', ', [Types::INTEGER, Types::STRING]);
            $fieldType = $this->getFieldType($field);
            $connectionType = Connection::PARAM_INT_ARRAY;
            
            switch ($fieldType) {
                case Types::BIGINT:
                case Types::INTEGER:
                case Types::SMALLINT:
                    break;
                case Types::STRING:
                    $connectionType = Connection::PARAM_STR_ARRAY;
                    break;
                default:
                    throw new DevelopmentException("Unsupported type found for field '{$field}'. It is allowed to use the IN operator only for types: '{$supportedTypes}'.");
                    break;
            }
            
            array_map(function($element) use($integerTypes, $stringTypes, $field, $fieldType, $valueTypes) {
                if ((in_array($element, $integerTypes) && !is_int($element)) || (in_array($element, $stringTypes) && !is_string($element))) {
                    $wrongType = gettype($element);
                    throw new DevelopmentException("Found the array element with type '{$wrongType}' that does not match type '{$fieldType}' for field '{$field}'. Valid types for array elements are: '{$valueTypes}'.");
                }
            }, $value);
                
                return new Parameter($name, $value,  $connectionType);
        }
        
        if ($this->useStrongTyping) {
            //dd( \Doctrine\DBAL\Types\Type::getTypesMap());
            $type = $this->getFieldType($field);
            
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

    /**
     *
     * @param string $referenceOrAlias
     * @throws \ItAces\ORM\DevelopmentException
     * @return string
     */
    protected function getFieldType(string $referenceOrAlias) : string
    {
        $targetEntity = $this->class;
        $pieces = explode('.', $referenceOrAlias);
        
        if (count($pieces) < 2) {
            throw new DevelopmentException("The passed field '{$referenceOrAlias}' name must contain a dot.");
        }
        
//         if (array_key_exists($pieces[0], $this->aliasMap)) {
//             $field = $this->aliasMap[$pieces[0]];
//             $pieces = explode('.', $field);
            
//             if (count($pieces) < 2) {
//                 throw new DevelopmentException("The passed field '{$field}' name must contain a dot.");
//             }
//         }
        
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
            
            if (array_key_exists($targetField, $classMetadata->associationMappings)) {
                $fieldMetadata = $classMetadata->associationMappings[$targetField];
                $targetEntity = $fieldMetadata['targetEntity'];
            } else if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
                throw new DevelopmentException("Unknown reference '{$targetField}' in '{$field}'.");
            }
            
            $index ++;
        }
        
        $classMetadata = $this->qb->getEntityManager()->getClassMetadata($targetEntity);
        
        if (! array_key_exists($targetField, $classMetadata->fieldMappings)) {
            throw new DevelopmentException("Unknown field '{$targetField}' in '{$field}'.");
        }
        
        $fieldMetadata = $classMetadata->fieldMappings[$targetField];
        
        return $fieldMetadata['type'];
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
