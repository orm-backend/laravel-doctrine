<?php

namespace ItAces;

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
     * @param \DateTime|null $deletedAt
     */
    public function setDeletedAt($deletedAt = null);
    
    /**
     * Get deletedAt.
     *
     * @return \DateTime|null
     */
    public function getDeletedAt();
    
    /**
     * Set deletedBy.
     *
     * @param \ItAces\Entity|null $deletedBy
     */
    public function setDeletedBy(Entity $deletedBy = null);
    
    /**
     * Get deletedBy.
     *
     * @return \ItAces\Entity|null
     */
    public function getDeletedBy();
    
}