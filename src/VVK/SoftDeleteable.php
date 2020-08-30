<?php

namespace VVK;

use VVK\ORM\Entities\Entity;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
interface SoftDeleteable
{
    
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
     * Set deletedBy.
     *
     * @param \VVK\ORM\Entities\Entity|null $deletedBy
     */
    public function setDeletedBy(Entity $deletedBy = null);
    
    /**
     * Get deletedBy.
     *
     * @return \VVK\ORM\Entities\Entity|null
     */
    public function getDeletedBy();
    
}
