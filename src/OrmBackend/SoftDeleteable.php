<?php

namespace OrmBackend;

use OrmBackend\ORM\Entities\Entity;

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
     * @param \OrmBackend\ORM\Entities\Entity|null $deletedBy
     */
    public function setDeletedBy(Entity $deletedBy = null);
    
    /**
     * Get deletedBy.
     *
     * @return \OrmBackend\ORM\Entities\Entity|null
     */
    public function getDeletedBy();
    
}
