<?php
namespace ItAces\View;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Str;
use ItAces\Utility\Helper;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class FieldContainer
{
    const INTERNAL_FIELDS = ['id', 'createdAt', 'updatedAt', 'deletedAt', 'createdBy', 'updatedBy', 'deleteBy'];
    
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
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
            if (!Str::contains($key, '_')) {
                continue;
            }
            
            [$classUrlName, $fieldName] = explode('_', $key);
            
            if (!$classUrlName || !$fieldName) {
                continue;
            }
            
            $className = Helper::classFromUlr($classUrlName);
            
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
            
            $wrapped->addField(BaseField::getInstance($classMetadata, $fieldName, $entity));
        }
        
        foreach (self::INTERNAL_FIELDS as $fieldName) {
            if ($fieldName == 'id' || array_search($fieldName, $classMetadata->fieldNames) === false) {
                continue;
            }
            
            $wrapped->addField(BaseField::getInstance($classMetadata, $fieldName, $entity));
        }
        
        return $wrapped;
    }

    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     */
    public function buildMetaFields(ClassMetadata $classMetadata)
    {
        $field = MetaField::getInstance($classMetadata, 'id');
        $this->fields[$field->fullname] = $field;
        
        foreach ($classMetadata->fieldNames as $fieldName) {
            if (array_search($fieldName, self::INTERNAL_FIELDS) !== false) {
                continue;
            }
            
            $field = MetaField::getInstance($classMetadata, $fieldName);
            $this->fields[$field->fullname] = $field;
        }
        
        foreach (self::INTERNAL_FIELDS as $fieldName) {
            if ($fieldName == 'id' || array_search($fieldName, $classMetadata->fieldNames) === false) {
                continue;
            }
            
            $field = MetaField::getInstance($classMetadata, $fieldName);
            $this->fields[$field->fullname] = $field;
        }
    }

    public function items()
    {
        return $this->items;
    }
    
    public function fields()
    {
        return $this->fields;
    }
    
    public function first()
    {
        return $this->items[0];
    }

}
