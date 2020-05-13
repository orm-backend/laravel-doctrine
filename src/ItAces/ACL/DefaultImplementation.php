<?php
namespace ItAces\ACL;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;


/**
 * This class is the default Access Control interface implementation. Lets read everything to any guest,
 * and only the super administrator can change anything.
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DefaultImplementation implements AccessControl
{
    /**
     * Gets default user permissions as bitmask
     * 
     * @param int $userId
     * @return int
     */
    protected function getDefaultPermissions(int $userId = null) : int
    {
        return $this->isSuperAdmin($userId) ? config('itaces.perms.full') : config('itaces.perms.guest.read');
    }

    /**
     * Gets user permissions as bitmask for given entity
     * 
     * @param string $classUrlName
     * @param int $userId
     * @return int
     */
    protected function getEntityPermissions(string $classUrlName, int $userId = null) : int
    {
        return 0;
    }
    
    /**
     * Is the current user a super administrator or not?
     * 
     * @param int $userId
     * @return bool
     */
    protected function isSuperAdmin(int $userId = null) : bool
    {
        return $userId === 1;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::permissions()
     */
    protected function permissions(string $classUrlName, int $userId = null) : int
    {
        
        $perms = $this->getEntityPermissions($classUrlName, $userId);
        
        if (!$perms) {
            $perms = $this->getDefaultPermissions($userId);
        }
        
        return $perms;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::hasCreateAccess()
     */
    protected function getCreateAccess(string $classUrlName, int $userId = null) : int
    {
        if ($this->isSuperAdmin($userId)) {
            return config('itaces.perms.entity.create');
        }
        
        $bitmask = config('itaces.perms.guest.create') | config('itaces.perms.entity.create');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('itaces.perms.forbidden')) ? 0 : $perms & $bitmask;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::getReadAccess()
     */
    protected function getReadAccess(string $classUrlName, int $userId = null) : int
    {
        if ($this->isSuperAdmin($userId)) {
            return config('itaces.perms.entity.read');
        }
        
        $bitmask = config('itaces.perms.guest.read') | config('itaces.perms.entity.read') | config('itaces.perms.record.read');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('itaces.perms.forbidden')) ? 0 : $perms & $bitmask;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::getUpdateAccess()
     */
    protected function getUpdateAccess(string $classUrlName, int $userId = null) : int
    {
        if ($this->isSuperAdmin($userId)) {
            return config('itaces.perms.entity.update');
        }
        
        $bitmask = config('itaces.perms.guest.update') | config('itaces.perms.entity.update') | config('itaces.perms.record.update');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('itaces.perms.forbidden')) ? 0 : $perms & $bitmask;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::getDeleteAccess()
     */
    protected function getDeleteAccess(string $classUrlName, int $userId = null) : int
    {
        if ($this->isSuperAdmin($userId)) {
            return config('itaces.perms.entity.delete');
        }
        
        $bitmask = config('itaces.perms.guest.delete') | config('itaces.perms.entity.delete') | config('itaces.perms.record.delete');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('itaces.perms.forbidden')) ? 0 : $perms & $bitmask;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::getRestoreAccess()
     */
    protected function getRestoreAccess(string $classUrlName, int $userId = null) : int
    {
        if ($this->isSuperAdmin($userId)) {
            return config('itaces.perms.entity.restore');
        }
        
        $bitmask = config('itaces.perms.entity.restore') | config('itaces.perms.record.restore');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('itaces.perms.forbidden')) ? 0 : $perms & $bitmask;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isAnyCreatingAllowed()
     */
    public function isAnyCreatingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getId() : null;
        $permissions = $this->getCreateAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.create'));
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.create'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isAnyReadingAllowed()
     */
    public function isAnyReadingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getId() : null;
        $permissions = $this->getReadAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.read'));
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.read'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isAnyUpdatingAllowed()
     */
    public function isAnyUpdatingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getId() : null;
        $permissions = $this->getUpdateAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.update'));
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.update'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isAnyDeletingAllowed()
     */
    public function isAnyDeletingAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getId() : null;
        $permissions = $this->getDeleteAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.delete'));
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.delete'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isAnyRestoringAllowed()
     */
    public function isAnyRestoringAllowed(?EntityBase $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getId() : null;
        $permissions = $this->getRestoreAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.restore'));
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.restore'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isReadingAllowed()
     */
    public function isReadingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        $userId = $user ? $user->getId() : null;
        $classUrlName = Helper::classToUlr(get_class($entity));
        $permissions = $this->getReadAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.read'));
        }
        
        if ($permissions & config('itaces.perms.record.read')) {
            return $userId === $entity->getCreatedBy()->getId();
        }

        return (bool) ($permissions & config('itaces.perms.entity.read'));
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isUpdatingAllowed()
     */
    public function isUpdatingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        $userId = $user ? $user->getId() : null;
        $classUrlName = Helper::classToUlr(get_class($entity));
        $permissions = $this->getUpdateAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.update'));
        }
        
        if ($permissions & config('itaces.perms.record.update')) {
            return $userId === $entity->getCreatedBy()->getId();
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.update'));
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isDeletingAllowed()
     */
    public function isDeletingAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        $userId = $user ? $user->getId() : null;
        $classUrlName = Helper::classToUlr(get_class($entity));
        $permissions = $this->getDeleteAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.delete'));
        }
        
        if ($permissions & config('itaces.perms.record.delete')) {
            return $userId === $entity->getCreatedBy()->getId();
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.delete'));
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::isRestoringAllowed()
     */
    public function isRestoringAllowed(?EntityBase $user, EntityBase $entity) : bool
    {
        $userId = $user ? $user->getId() : null;
        $classUrlName = Helper::classToUlr(get_class($entity));
        $permissions = $this->getRestoreAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('itaces.perms.guest.restore', 0));
        }
        
        if ($permissions & config('itaces.perms.record.restore')) {
            return $userId === $entity->getCreatedBy()->getId();
        }
        
        return (bool) ($permissions & config('itaces.perms.entity.restore'));
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\ACL\AccessControl::addRecordsFilter()
     */
    public function addRecordsFilter(string $class, array $parameters = [], string $alias = null) : array
    {
        $classUrlName = Helper::classToUlr($class);
        $permissions = $this->getReadAccess($classUrlName);
        
        if (!$permissions) {
            throw new AuthorizationException();
        }
        
        if ($permissions === config('itaces.perms.record.read')) {
            $alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
            
            if (!array_key_exists('filter', $parameters)) {
                $parameters['filter'] = [];
            }
            
            $parameters['filter'][] = [
                $alias . '.createdBy.id',
                'eq',
                Auth::id()
            ];
        }
        
        return $parameters;
    }

}
