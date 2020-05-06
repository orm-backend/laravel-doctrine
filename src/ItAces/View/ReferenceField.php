<?php

namespace ItAces\View;

use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ReferenceField extends MetaField
{
    
    /**
     * 
     * @var string
     */
    public $refClassUrlName;
    
    /**
     *
     * @var string
     */
    public $refClassAlias;

    /**
     *
     * @var string
     */
    public $valueName;
    
    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     * @param \ItAces\ORM\Entities\EntityBase $entity
     * @return \ItAces\View\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, EntityBase $entity = null)
    {
        $instance = parent::getInstance($classMetadata, $fieldName);
        $associationMapping = $classMetadata->getAssociationMapping($fieldName);
        $instance->refClassUrlName = Helper::classToUlr($associationMapping['targetEntity']);
        $instance->refClassAlias = lcfirst((new \ReflectionClass($associationMapping['targetEntity']))->getShortName());
        
        if ($entity && array_search($fieldName, FieldContainer::FORBIDDEN_FIELDS) === false) {
            /**
             * 
             * @var \ItAces\ORM\Entities\EntityBase $reference
             */
            $reference = $classMetadata->getFieldValue($entity, $fieldName);
            
            if ($reference) {
                $instance->value = $reference->getId();
                /**
                 * 
                 * @var \Doctrine\ORM\EntityManager $em
                 */
                $em = app('em');
                $refClassMetadata = $em->getClassMetadata($associationMapping['targetEntity']);
                
                if ($refClassMetadata->hasField('name')) {
                    $instance->valueName = Str::limit( $refClassMetadata->getFieldValue($reference, 'name'), 50 );
                } else {
                    $instance->valueName = $instance->value;
                }
            }
        }
        
        return $instance;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\MetaField::getHtmlType()
     */
    protected function getHtmlType()
    {
        return 'reference';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\MetaField::getDefaultSortable()
     */
    protected function getDefaultSortable()
    {
        return 'true';
    }

}
