<?php

namespace ItAces\ORM\Entities;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\DBAL\Types\Types;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ItAces\ORM\DevelopmentException;
use ItAces\Types\FileType;

abstract class BaseEntity implements Entity
{
    private static $identifiers = [];

    static public function getIdentifier() {
        if (!array_key_exists(static::class, self::$identifiers)) {
            /**
             *
             * @var \Doctrine\ORM\EntityManager $em
             */
            $em = app('em');
            $classMetadata = $em->getClassMetadata(static::class);
            $fieldName = $classMetadata->getSingleIdentifierFieldName();
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            self::$identifiers[static::class] = [
                'name' => $fieldName,
                'type' => $fieldMapping['type']
            ];
        }

        return self::$identifiers[static::class];
    }
    
    static public function getIdentifierName() {
        $identifier = self::getIdentifier();
        
        return $identifier['name'];
    }
    
    static public function getIdentifierType() {
        $identifier = self::getIdentifier();

        return $identifier['type'];
    }
    
    /**
     * @var \Carbon\Carbon
     */
    protected $createdAt;
    
    /**
     * @var \Carbon\Carbon|null
     */
    protected $updatedAt;
    
    /**
     * @var \Carbon\Carbon|null
     */
    protected $deletedAt;
    
    /**
     * @var \ItAces\ORM\Entities\Entity
     */
    protected $createdBy;
    
    /**
     * @var \ItAces\ORM\Entities\Entity
     */
    protected $deletedBy;
    
    /**
     * @var \ItAces\ORM\Entities\Entity
     */
    protected $updatedBy;
    
    /**
     * Get validation rules
     * @return array
     */
    abstract public function getModelValidationRules();
    
    /**
     * Get request validation rules
     * @return array
     */
    abstract public static function getRequestValidationRules();
    
    /**
     * Get id.
     * 
     * @deprecated
     * @return mixed
     */
    public function getId()
    {
        return $this->getPrimary();
    }
    
    public function getPrimary()
    {
        $identifier = self::getIdentifier();
        $value = $this->{$identifier['name']};
        
        if ($this->{$identifier['name']} !== null) {
            switch ($identifier['type']) {
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
            }
        }
        
        return $value;
    }
    
    /**
     * Set createdAt.
     *
     * @param \Carbon\Carbon $createdAt
     *
     * @return \ItAces\ORM\Entities\Entity
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
    
    /**
     * Get createdAt.
     *
     * @return \Carbon\Carbon
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Set updatedAt.
     *
     * @param \Carbon\Carbon|null $updatedAt
     *
     * @return \ItAces\ORM\Entities\Entity
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }
    
    /**
     * Set createdBy.
     *
     * @param \ItAces\ORM\Entities\Entity $createdBy
     *
     * @return \ItAces\ORM\Entities\Entity
     */
    public function setCreatedBy(Entity $createdBy)
    {
        $this->createdBy = $createdBy;
        
        return $this;
    }
    
    /**
     * Get createdBy.
     *
     * @return \ItAces\ORM\Entities\Entity
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    
    /**
     * Set deletedBy.
     *
     * @param \ItAces\ORM\Entities\Entity|null $deletedBy
     */
    public function setDeletedBy(Entity $deletedBy = null)
    {
        $this->deletedBy = $deletedBy;
    }
    
    /**
     * Get deletedBy.
     *
     * @return \ItAces\ORM\Entities\Entity|null
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }
    
    /**
     * Set updatedBy.
     *
     * @param \ItAces\ORM\Entities\Entity|null $updatedBy
     *
     * @return \ItAces\ORM\Entities\Entity
     */
    public function setUpdatedBy(Entity $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;
        
        return $this;
    }
    
    /**
     * Get updatedBy.
     *
     * @return \ItAces\ORM\Entities\Entity|null
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
    
    /**
     * Get updatedAt.
     *
     * @return \Carbon\Carbon|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    /**
     * Set deletedAt.
     *
     * @param \Carbon\Carbon|null $deletedAt
     */
    public function setDeletedAt($deletedAt = null)
    {
        $this->deletedAt = $deletedAt;
    }
    
    /**
     * Get deletedAt.
     *
     * @return \Carbon\Carbon|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
    
    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeAdd(LifecycleEventArgs $event)
    {
        $this->createdAt = now();
        
        if (Auth::id()) {
            $this->createdBy = Auth::user();
        }
        
        $this->validate();
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterAdd(LifecycleEventArgs $event)
    {
        // Add your code here
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeUpdate(LifecycleEventArgs $event)
    {
        $this->updatedAt = now();
        
        if (Auth::id()) {
            $this->updatedBy = Auth::user();
        }
        
        $this->validate();
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterUpdate(LifecycleEventArgs $event)
    {
        // Add your code here
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeDelete(LifecycleEventArgs $event)
    {
        // Add your code here
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterDelete(LifecycleEventArgs $event)
    {
        if ($this instanceof FileType) {
            /**
             * 
             * @var \ItAces\Types\FileType $file
             */
            $file = $this;
            Storage::delete($file->getPath());
        }
    }
    
    protected function validate()
    {
        $attributes = [];
        $rules = $this->getModelValidationRules();
        $fields = array_keys($rules);
        
        foreach ($fields as $field) {
            $method = 'is' . ucfirst($field);
            
            if (!method_exists($this, $method)) {
                $method = 'get' . ucfirst($field);
            }
            
            $attributes[$field] = $this->$method();
        }
        
        $validator = Validator::make($attributes, $rules);
        $validator->validate();
    }

    public function __get($property)
    {
        $field = Str::camel($property);

        if (!property_exists($this, $field)) {
            if (method_exists($this, $field)) {
                return $this->{$field}();
            }
            
            $class = get_class($this);
            throw new DevelopmentException("Property '{$property}' not found on '{$class}' entity.");
        }
        
        return $this->{$field};
    }
    
    public function __set($property, $value)
    {
        $field = Str::camel($property);
        
        if (!property_exists($this, $field)) {
            $class = get_class($this);
            throw new DevelopmentException("Property '{$property}' not found on '{$class}' entity.");
        }
        
        $this->{$field} = $value;
    }

}
