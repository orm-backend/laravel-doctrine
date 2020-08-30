<?php
namespace VVK\ACL;

use VVK\ORM\Entities\Entity;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Policies
{
    /**
     * 
     * @var \VVK\ACL\AccessControl
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
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyCreatingAllowed(?Entity $user, string $classUrlName) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isAnyCreatingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyReadingAllowed(?Entity $user, string $classUrlName) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isAnyReadingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyUpdatingAllowed(?Entity $user, string $classUrlName) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isAnyUpdatingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyDeletingAllowed(?Entity $user, string $classUrlName) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isAnyDeletingAllowed($user, $classUrlName);
    }
    
    /**
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyRestoringAllowed(?Entity $user, string $classUrlName) : bool
    {
        return config('itaces.softdelete', true) && ($this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isAnyRestoringAllowed($user, $classUrlName));
    }
    
    /**
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isReadingAllowed(?Entity $user, Entity $entity) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isReadingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isUpdatingAllowed(?Entity $user, Entity $entity) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isUpdatingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isDeletingAllowed(?Entity $user, Entity $entity) : bool
    {
        return $this->acl->isSuperAdmin( $user ? $user->getId() : null ) || $this->acl->isDeletingAllowed($user, $entity);
    }
    
    /**
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isRestoringAllowed(?Entity $user, Entity $entity) : bool
    {
        return config('itaces.softdelete', true) && $this->acl->isRestoringAllowed($user, $entity);
    }
    
}
