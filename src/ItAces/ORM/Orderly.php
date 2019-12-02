<?php

namespace ItAces\ORM;

use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Orderly
{

    const SANITIZE_STRING_FLAG = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK;
    
    
    /**
     * 
     * @param array $fieldMetadata
     * @param string $value
     * @return mixed
     */
    public function sanitizeString(array $fieldMetadata, string $value)
    {
        // What TODO with nuls and empty strings?
        if (is_null($value)) {
            return $value;
        }

        $value = filter_var(trim($value), FILTER_SANITIZE_SPECIAL_CHARS, self::SANITIZE_STRING_FLAG);
        
        if ($fieldMetadata['type'] == 'string' && $fieldMetadata['length']) {
            $value = mb_substr($value, 0, $fieldMetadata['length']);
        }

        //dd( \Doctrine\DBAL\Types\Type::getTypesMap());
        switch ($fieldMetadata['type']) {
            case Types::INTEGER:
            case Types::SMALLINT:
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                $value = (int) $value;
                break;
            case Types::FLOAT:
            case Types::DECIMAL:
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT);
                $value = (float) $value;
                break;
            case Types::BOOLEAN:
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                $value = (boolean) $value;
                break;
            case Types::DATE_MUTABLE:
            case Types::DATETIME_MUTABLE:
            case Types::DATETIMETZ_MUTABLE:
                //TODO case Types::TIME_MUTABLE:
                $timeZone = null;
                
                if (auth()->id() && method_exists(auth()->user(), 'getTimezone')) {
                    $timeZone = auth()->user()->getTimezone();
                }
                
                $value = Carbon::parse($value, $timeZone);
                break;
                
        }
        
        return $value;
    }
    
    /**
     * 
     * @param array $fieldMetadata
     * @param array $value
     * @throws DevelopmentException
     * @return array
     */
    public function sanitizeArray(array $fieldMetadata, array $value) : array
    {
        $fieldName = $fieldMetadata['fieldName'];
        $fieldType = $fieldMetadata['type'];
        $length = $fieldType == 'string' && $fieldMetadata['length'] ? $fieldMetadata['length'] : null;
        $integerTypes = [Types::INTEGER, Types::SMALLINT];
        $stringTypes = [Types::STRING, Types::BIGINT];
        $allowedTypes = implode(', ', array_merge($integerTypes, $stringTypes));
        $connectionType = Connection::PARAM_INT_ARRAY;
        
        switch ($fieldType) {
            case Types::INTEGER:
            case Types::SMALLINT:
                break;
            case Types::BIGINT: // I intend to use bigint as string
            case Types::STRING:
                $connectionType = Connection::PARAM_STR_ARRAY;
                break;
            default:
                throw new DevelopmentException("Unsupported type found for field '{$fieldName}'. It is allowed to use the array of values only for types: '{$allowedTypes}'.");
                break;
        }
        
        $value = array_map(function($element) use($fieldType, $integerTypes, $stringTypes, $length) {
            // What TODO with nuls and empty strings?
            if (is_null($element)) {
                return $element;
            }
            
            $integerType = in_array($fieldType, $integerTypes);
            $stringType = in_array($fieldType, $stringTypes);
            
            if (!is_string($element) && !is_int($element)) {
                $valueType = gettype($element);
                throw new DevelopmentException("The value type '{$valueType}' is incompatible with column type '{$fieldType}'.");
            }
            
            if (is_string($element)) {
                $element = filter_var(trim($element), FILTER_SANITIZE_SPECIAL_CHARS, self::SANITIZE_STRING_FLAG);
                
                if ($integerType) {
                    $element = filter_var($element, FILTER_SANITIZE_NUMBER_INT);
                } else if ($stringType && $length) {
                    $element = mb_substr($element, 0, $length);
                }
            }
            
            if ($stringType && !is_string($element)) {
                $element = (string) $element;
            }
            
            if ($integerType && !is_int($element)) {
                $element = (int) $element;
            }
            
            return $element;
        }, $value);
        
        return [$value, $connectionType];
    }

}