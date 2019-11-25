<?php

namespace ItAces\Filters;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class SoftDeleteFilter extends SQLFilter
{
    
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        // Check if the entity implements the SoftDeleteable interface
        if ($targetEntity->reflClass->implementsInterface('ItAces\SoftDeleteable')) {
            return $targetTableAlias.'.deleted_at IS NULL';
        }
        
        return '';
    }

}