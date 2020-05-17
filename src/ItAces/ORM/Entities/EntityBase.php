<?php

namespace ItAces\ORM\Entities;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ItAces\SoftDeleteable;
use ItAces\ORM\DevelopmentException;
use ItAces\Types\FileType;

abstract class EntityBase implements SoftDeleteable
{

    /**
     * @var int
     */
    protected $id;
    
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
     * @var \ItAces\ORM\Entities\EntityBase
     */
    protected $createdBy;
    
    /**
     * @var \ItAces\ORM\Entities\EntityBase
     */
    protected $deletedBy;
    
    /**
     * @var \ItAces\ORM\Entities\EntityBase
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set createdAt.
     *
     * @param \Carbon\Carbon $createdAt
     *
     * @return \ItAces\ORM\Entities\EntityBase
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
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }
    
    /**
     * Set createdBy.
     *
     * @param \ItAces\ORM\Entities\EntityBase $createdBy
     *
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function setCreatedBy(EntityBase $createdBy)
    {
        $this->createdBy = $createdBy;
        
        return $this;
    }
    
    /**
     * Get createdBy.
     *
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    
    /**
     * Set deletedBy.
     *
     * @param \ItAces\ORM\Entities\EntityBase|null $deletedBy
     */
    public function setDeletedBy(EntityBase $deletedBy = null)
    {
        $this->deletedBy = $deletedBy;
    }
    
    /**
     * Get deletedBy.
     *
     * @return \ItAces\ORM\Entities\EntityBase|null
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }
    
    /**
     * Set updatedBy.
     *
     * @param \ItAces\ORM\Entities\EntityBase|null $updatedBy
     *
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function setUpdatedBy(EntityBase $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;
        
        return $this;
    }
    
    /**
     * Get updatedBy.
     *
     * @return \ItAces\ORM\Entities\EntityBase|null
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
            $method = 'get' . ucfirst($field);
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
