<?php
namespace ItAces\View;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\DBAL\Types\EnumType;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class FieldContainer
{
    const INTERNAL_FIELDS = ['id', 'createdAt', 'updatedAt', 'deletedAt', 'createdBy', 'updatedBy', 'deletedBy'];
    
    const FORBIDDEN_FIELDS = ['password', 'rememberToken'];
    
    /**
     * 
     * @var \ItAces\View\MetaField[]
     */
    protected $fields = [];
    
    /**
     *
     * @var \ItAces\View\WrappedEntity[]
     */
    protected $items = [];
    
    /**
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * 
     * @var boolean
     */
    protected $fetchAllPosibleCollectionValues;
    
    protected $enumTypes = [];
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param bool $fetchAllPosibleCollectionValues
     */
    public function __construct(EntityManager $em, bool $fetchAllPosibleCollectionValues = null)
    {
        $this->em = $em;
        $this->fetchAllPosibleCollectionValues = $fetchAllPosibleCollectionValues;
        
        $customTypes = config('doctrine.custom_types');
        
        foreach ($customTypes as $name => $class) {
            if ((new \ReflectionClass($class))->isSubclassOf(EnumType::class)) {
                $this->enumTypes[$name] = $class;
            }
        }
    }
    
    /**
     *
     * @param array $data
     * @return array
     */
    public static function readRequest(array $data) : array
    {
        $map = [];

        foreach ($data as $key => $value) {
            $lastUnderscore = strripos($key, '_');
            
            if (!$lastUnderscore) {
                continue;
            }
            
            $classUrlName = substr($key, 0, strripos($key, '_'));
            $fieldName = substr($key, strripos($key, '_') + 1);
            $className = Helper::classFromUlr($classUrlName);
            
            if (!$classUrlName || !$fieldName) {
                continue;
            }

            if (!array_key_exists($className, $map)) {
                $map[$className] = [];
            }
            
            $map[$className][$fieldName] = $value;
        }

        return $map;
    }
    
    /**
     * 
     * @param \ItAces\ORM\Entities\EntityBase $entity
     */
    public function addEntity(EntityBase $entity)
    {
        $className = get_class($entity);
        $classMetadata = $this->em->getClassMetadata($className);
        $this->items[] = $this->wrapEntity($classMetadata, $entity);
    }
    
    /**
     * 
     * @param \ItAces\ORM\Entities\EntityBase[] $data
     */
    public function addCollection(array $data)
    {
        foreach ($data as $entity) {
            $this->addEntity($entity);
        }
    }
    
    /**
     *
     * @param ClassMetadata $classMetadata
     * @param EntityBase $instance
     * @return \ItAces\View\WrappedEntity
     */
    protected function wrapEntity(ClassMetadata $classMetadata, EntityBase $entity) : WrappedEntity
    {
        $wrapped = new WrappedEntity($entity->getId());
        $wrapped->addField(BaseField::getInstance($classMetadata, 'id', $entity));
        
        foreach ($classMetadata->fieldNames as $fieldName) {
            if (array_search($fieldName, self::INTERNAL_FIELDS) !== false) {
                continue;
            }

            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            
            if (array_key_exists($fieldMapping['type'], $this->enumTypes)) {
                $enumField = EnumField::getInstance($classMetadata, $fieldName, $entity);
                $enumField->initOptions($this->enumTypes[$fieldMapping['type']]);
                $wrapped->addField($enumField);
                continue;
            }
            
            $wrapped->addField(BaseField::getInstance($classMetadata, $fieldName, $entity));
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            if (array_search($associationMapping['fieldName'], self::INTERNAL_FIELDS) !== false) {
                continue;
            }
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                if (!$associationMapping['isOwningSide']) {
                    continue;
                }
                
                $wrapped->addField(ReferenceField::getInstance($classMetadata, $associationMapping['fieldName'], $entity));
            } else if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                if (!$associationMapping['isOwningSide']) {
                    continue;
                }
                
                $collectionField = CollectionField::getInstance($classMetadata, $associationMapping['fieldName'], $entity);
                
                if ($this->fetchAllPosibleCollectionValues) {
                    $collectionField->fetchAllValues();
                }
                
                $wrapped->addField($collectionField);
            }
        }
        
        foreach (self::INTERNAL_FIELDS as $fieldName) {
            if ($fieldName == 'id') {
                continue;
            }
            
            if (array_search($fieldName, $classMetadata->fieldNames) !== false) {
                $wrapped->addField(BaseField::getInstance($classMetadata, $fieldName, $entity));
            } else if ($classMetadata->hasAssociation($fieldName)) {
                $wrapped->addField(ReferenceField::getInstance($classMetadata, $fieldName, $entity));
            }
        }
        
        return $wrapped;
    }

    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     */
    public function buildMetaFields(ClassMetadata $classMetadata)
    {
        $this->fields[] = BaseField::getInstance($classMetadata, 'id');
        
        foreach ($classMetadata->fieldNames as $fieldName) {
            if (array_search($fieldName, self::INTERNAL_FIELDS) !== false) {
                continue;
            }
            
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            
            if (array_key_exists($fieldMapping['type'], $this->enumTypes)) {
                $enumField = EnumField::getInstance($classMetadata, $fieldName);
                $enumField->initOptions($this->enumTypes[$fieldMapping['type']]);
                $this->fields[] = $enumField;
                continue;
            }

            $this->fields[] = BaseField::getInstance($classMetadata, $fieldName);
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            if (array_search($associationMapping['fieldName'], self::INTERNAL_FIELDS) !== false) {
                continue;
            }
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                if (!$associationMapping['isOwningSide']) {
                    continue;
                }

                $this->fields[] = ReferenceField::getInstance($classMetadata, $associationMapping['fieldName']);
            } else if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                if (!$associationMapping['isOwningSide']) {
                    continue;
                }
                
                $collectionField = CollectionField::getInstance($classMetadata, $associationMapping['fieldName']);
                
                if ($this->fetchAllPosibleCollectionValues) {
                    $collectionField->fetchAllValues();
                }
                
                $this->fields[] = $collectionField;
            }
        }
        
        foreach (self::INTERNAL_FIELDS as $fieldName) {
            if ($fieldName == 'id') {
                continue;
            }

            if (array_search($fieldName, $classMetadata->fieldNames) !== false) {
                $this->fields[] = BaseField::getInstance($classMetadata, $fieldName);
            } else if ($classMetadata->hasAssociation($fieldName)) {
                $this->fields[] = ReferenceField::getInstance($classMetadata, $fieldName);
            }
            
        }
    }

    /**
     * 
     * @return \ItAces\View\WrappedEntity[]
     */
    public function items()
    {
        return $this->items;
    }
    
    /**
     * 
     * @return \ItAces\View\MetaField[]
     */
    public function fields()
    {
        return $this->fields;
    }
    
    /**
     * 
     * @return \ItAces\View\WrappedEntity
     */
    public function first()
    {
        return $this->items[0];
    }

}
