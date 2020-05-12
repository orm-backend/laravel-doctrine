<?php
namespace ItAces\ACL;

use ItAces\ORM\Entities\EntityBase;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Policies
{
    /**
     * 
     * @var \ItAces\ACL\AccessControl
     */
    protected $acl;
    
    /**
     * 
     * @param AccessControl $acl
     */
    public function __construct(AccessControl $acl) {
        $this->acl = $acl;
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyCreatingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        return $this->acl->isAnyCreatingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyReadingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        return $this->acl->isAnyReadingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyUpdatingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        return $this->acl->isAnyUpdatingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyDeletingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        return $this->acl->isAnyDeletingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyRestoringAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        return $this->acl->isAnyRestoringAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isReadingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        return $this->acl->isReadingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isUpdatingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        return $this->acl->isUpdatingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isDeletingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        return $this->acl->isDeletingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isRestoringAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        return $this->acl->isRestoringAllowed($user, $entity);
    }
    
}
