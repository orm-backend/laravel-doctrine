<?php
namespace ItAces\ORM\Entities;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

interface Entity
{
    /**
     * Get cached identifier name and type
     * @return array
     */
    public static function getIdentifier();
    
    /**
     * @return array
     */
    public static function getRequestValidationRules();
    
    /**
     * Get id.
     *
     * @deprecated Use getPrimary() instead
     * @return mixed
     */
    public function getId();
    
    /**
     * @return mixed
     */
    public function getPrimary();
    
    /**
     * @return array
     */
    public function getModelValidationRules();
    
    /**
     * Set createdAt.
     *
     * @param \Carbon\Carbon $createdAt
     */
    public function setCreatedAt($createdAt);
    
    /**
     * Get createdAt.
     *
     * @return \Carbon\Carbon
     */
    public function getCreatedAt();
    
    /**
     * Set updatedAt.
     *
     * @param \Carbon\Carbon|null $updatedAt
     */
    public function setUpdatedAt($updatedAt = null);
    
    
    /**
     * Set createdBy.
     *
     * @param \ItAces\ORM\Entities\Entity $createdBy
     */
    public function setCreatedBy(Entity $createdBy);
    
    /**
     * Set deletedBy.
     *
     * @param \ItAces\ORM\Entities\Entity|null $deletedBy
     */
    public function setDeletedBy(Entity $deletedBy = null);
    
    /**
     * Get deletedBy.
     *
     * @return \ItAces\ORM\Entities\Entity|null
     */
    public function getDeletedBy();
    
    /**
     * Get updatedBy.
     *
     * @return \ItAces\ORM\Entities\Entity|null
     */
    public function getUpdatedBy();
    
    /**
     * Get updatedAt.
     *
     * @return \Carbon\Carbon|null
     */
    public function getUpdatedAt();
    
    /**
     * Set deletedAt.
     *
     * @param \Carbon\Carbon|null $deletedAt
     */
    public function setDeletedAt($deletedAt = null);
    
    /**
     * Get deletedAt.
     *
     * @return \Carbon\Carbon|null
     */
    public function getDeletedAt();
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeAdd(LifecycleEventArgs $event);
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterAdd(LifecycleEventArgs $event);
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeUpdate(LifecycleEventArgs $event);
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterUpdate(LifecycleEventArgs $event);
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onBeforeDelete(LifecycleEventArgs $event);
    
    /**
     *
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $event
     */
    public function onAfterDelete(LifecycleEventArgs $event);
    
}