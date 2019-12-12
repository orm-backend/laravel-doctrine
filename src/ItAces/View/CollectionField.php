<?php

namespace ItAces\View;

use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;

class CollectionField extends MetaField
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
    public $refClassTitle;
    
    /**
     *
     * @var string
     */
    public $refClassAlias;
    
    /**
     * 
     * @var \stdClass[]
     */
    public $allValues = [];
    
    /**
     *
     * @var string
     */
    protected $targetEntity;
    
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
        $instance->targetEntity = $associationMapping['targetEntity'];
        $refClassShortName = (new \ReflectionClass($associationMapping['targetEntity']))->getShortName();
        $instance->refClassAlias = lcfirst($refClassShortName);
        $instance->refClassTitle = __(Str::pluralCamelWords( ucfirst($refClassShortName), 2));
        
        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        $refClassMetadata = $em->getClassMetadata($instance->targetEntity);
        $instance->value = [];
        
        if ($entity && array_search($fieldName, FieldContainer::FORBIDDEN_FIELDS) === false) {
            /**
             *
             * @var \ItAces\ORM\Entities\EntityBase[] $collection
             */
            $collection = $classMetadata->getFieldValue($entity, $fieldName);
            
            if ($collection) {
                foreach ($collection as $reference) {
                    $wrapped = new \stdClass;
                    $wrapped->selected = true;
                    $wrapped->id = $reference->getId();
                    
                    if ($refClassMetadata->hasField('name')) {
                        $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($reference, 'name'), 50 );
                    } else if ($refClassMetadata->hasField('code')) {
                        $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($reference, 'code'), 50 );
                    } else {
                        $wrapped->name = $wrapped->value;
                    }
                    
                    $instance->value[$wrapped->id] = $wrapped;
                }
            }
        }
        
        return $instance;
    }
    
    public function fetchAllValues()
    {
        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        /**
         *
         * @var \ItAces\ORM\Entities\EntityBase[] $collection
         */
        $collection = $em->getRepository($this->targetEntity)->findAll();
        $refClassMetadata = $em->getClassMetadata($this->targetEntity);
        
        if ($collection) {
            $tmp = [];
            
            foreach ($collection as $reference) {
                $wrapped = new \stdClass;
                $wrapped->id = $reference->getId();
                $wrapped->selected = array_key_exists($wrapped->id, $this->value);
                
                if ($refClassMetadata->hasField('name')) {
                    $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($reference, 'name'), 50 );
                } else if ($refClassMetadata->hasField('code')) {
                    $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($reference, 'code'), 50 );
                } else {
                    $wrapped->name = $wrapped->value;
                }
                
                $tmp[$wrapped->id] = $wrapped;
            }
            
            $this->value = $tmp;
        }
        
        
    }
    
    protected function getHtmlType()
    {
        return 'collection';
    }

    protected function getDefaultSortable()
    {
        return 'false';
    }
    
}
