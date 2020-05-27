<?php

namespace ItAces\View;

use Doctrine\ORM\Mapping\ClassMetadata;
use Illuminate\Support\Facades\Storage;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;
use ItAces\Types\FileType;
use ItAces\Types\ImageType;

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
    
//     /**
//      * 
//      * @var \stdClass[]
//      */
//     public $allValues = [];
    
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
     * @param int $index
     * @return \ItAces\View\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, EntityBase $entity = null, int $index = null)
    {
        $instance = parent::getInstance($classMetadata, $fieldName, $entity, $index);
        
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
                foreach ($collection as $element) {
                    $wrapped = new \stdClass;
                    $wrapped->id = $element->getId();
                    $wrapped->selected = true;

//                     if ($element instanceof ImageType) {
//                         $wrapped->type = 'image';
//                     } else if ($element instanceof FileType) {
//                         $wrapped->type = 'file';
//                     } else {
//                         $wrapped->type = 'common';
//                     }
                    
                    if ($refClassMetadata->hasField('name')) {
                        $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($element, 'name'), 50 );
                    } else if ($refClassMetadata->hasField('code')) {
                        $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($element, 'code'), 50 );
                    } else {
                        $wrapped->name = $wrapped->id;
                    }

                    if ($element instanceof FileType) {
                        $wrapped->path = $element->getPath();
                    }
                    
                    if ($element instanceof ImageType) {
                        $wrapped->url = Storage::url($wrapped->path);
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
            
            foreach ($collection as $element) {
                $wrapped = new \stdClass;
                $wrapped->id = $element->getId();
                $wrapped->selected = array_key_exists($wrapped->id, $this->value);
                
                if ($refClassMetadata->hasField('name')) {
                    $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($element, 'name'), 50 );
                } else if ($refClassMetadata->hasField('code')) {
                    $wrapped->name = Str::limit( $refClassMetadata->getFieldValue($element, 'code'), 50 );
                } else {
                    $wrapped->name = $wrapped->id;
                }
                
                if ($element instanceof FileType) {
                    $wrapped->path = $element->getPath();
                }
                
                if ($element instanceof ImageType) {
                    $wrapped->url = Storage::url($wrapped->path);
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
