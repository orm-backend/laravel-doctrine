<?php
namespace OrmBackend\ACL;

use App\Model\Role;
use App\Model\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use OrmBackend\ORM\Entities\Entity;
use OrmBackend\Utility\Helper;


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
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * 
     */
    public function __construct()
    {
        $this->em = app('em');
    }
    
    /**
     * Gets default user permissions as bitmask
     * 
     * @param int $userId
     * @return int
     */
    protected function getDefaultPermissions(int $userId = null) : int
    {
        $permissions = 0;
        $roles = $this->getUserRoles($userId);

        if ($roles) {
            foreach ($roles as $role) {
                $permissions = $permissions | $role->getPermission();
            }
        }
        
        return $permissions ? $permissions : config('ormbackend.perms.forbidden');
    }

    /**
     * Gets user permissions as bitmask for given entity
     * 
     * @param string $classUrlName
     * @param int $userId
     * @return int|null
     */
    protected function getEntityPermissions(string $classUrlName, int $userId = null)
    {
        return null;
    }
    
    /**
     * Is the current user a super administrator or not?
     * 
     * @param int $userId
     * @return bool
     */
    public function isSuperAdmin(int $userId = null) : bool
    {
        return $userId === 1;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::permissions()
     */
    protected function permissions(string $classUrlName, int $userId = null) : int
    {
        
        $perms = $this->getEntityPermissions($classUrlName, $userId);

        if ($perms === null) {
            $perms = $this->getDefaultPermissions($userId);
        }
        
        return $perms;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::hasCreateAccess()
     */
    protected function getCreateAccess(string $classUrlName, int $userId = null) : int
    {
        $bitmask = config('ormbackend.perms.guest.create') | config('ormbackend.perms.entity.create');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('ormbackend.perms.forbidden')) ? 0 : ($perms & $bitmask);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::getReadAccess()
     */
    protected function getReadAccess(string $classUrlName, int $userId = null) : int
    {
        $bitmask = config('ormbackend.perms.guest.read') | config('ormbackend.perms.entity.read') | config('ormbackend.perms.record.read');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('ormbackend.perms.forbidden')) ? 0 : ($perms & $bitmask);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::getUpdateAccess()
     */
    protected function getUpdateAccess(string $classUrlName, int $userId = null) : int
    {
        $bitmask = config('ormbackend.perms.guest.update') | config('ormbackend.perms.entity.update') | config('ormbackend.perms.record.update');
        $perms = $this->permissions($classUrlName, $userId);

        return ($perms & config('ormbackend.perms.forbidden')) ? 0 : ($perms & $bitmask);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::getDeleteAccess()
     */
    protected function getDeleteAccess(string $classUrlName, int $userId = null) : int
    {
        $bitmask = config('ormbackend.perms.guest.delete') | config('ormbackend.perms.entity.delete') | config('ormbackend.perms.record.delete');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('ormbackend.perms.forbidden')) ? 0 : ($perms & $bitmask);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::getRestoreAccess()
     */
    protected function getRestoreAccess(string $classUrlName, int $userId = null) : int
    {
        $bitmask = config('ormbackend.perms.entity.restore') | config('ormbackend.perms.record.restore');
        $perms = $this->permissions($classUrlName, $userId);
        
        return ($perms & config('ormbackend.perms.forbidden')) ? 0 : ($perms & $bitmask);
    }
    
    /**
     *
     * @param int $userId
     * @return \App\Model\Role[]
     */
    protected function getUserRoles(int $userId = null)
    {
        $roles = [];
        
        if (!$userId) {
            $role = $this->em->getRepository(Role::class)->findOneBy(['code' => config('ormbackend.roles.guest', 'guest')]);
            
            if ($role) {
                $roles[] = $role;
            }
        } else {
            /**
             *
             * @var \App\Model\User $user
             */
            $user = $this->em->getRepository(User::class)->find($userId);
            $roles = $user->getRoles()->getValues();
        }

        return $roles;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isAnyCreatingAllowed()
     */
    public function isAnyCreatingAllowed(?Entity $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $permissions = $this->getCreateAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.create'));
        }
        
        return (bool) ($permissions & config('ormbackend.perms.entity.create'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isAnyReadingAllowed()
     */
    public function isAnyReadingAllowed(?Entity $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $permissions = $this->getReadAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.read'));
        }

        return ($permissions & config('ormbackend.perms.entity.read')) || ($permissions & config('ormbackend.perms.record.read'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isAnyUpdatingAllowed()
     */
    public function isAnyUpdatingAllowed(?Entity $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $permissions = $this->getUpdateAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.update'));
        }
        
        return ($permissions & config('ormbackend.perms.entity.update')) || ($permissions & config('ormbackend.perms.record.update'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isAnyDeletingAllowed()
     */
    public function isAnyDeletingAllowed(?Entity $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $permissions = $this->getDeleteAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.delete'));
        }
        
        return ($permissions & config('ormbackend.perms.entity.delete')) || ($permissions & config('ormbackend.perms.record.delete'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isAnyRestoringAllowed()
     */
    public function isAnyRestoringAllowed(?Entity $user, string $classUrlName) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $permissions = $this->getRestoreAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.restore'));
        }
        
        return ($permissions & config('ormbackend.perms.entity.restore')) || ($permissions & config('ormbackend.perms.record.restore'));
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isReadingAllowed()
     */
    public function isReadingAllowed(?Entity $user, Entity $entity) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $classUrlName = Helper::classToUrl(get_class($entity));
        $permissions = $this->getReadAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.read'));
        }

        if ($permissions & config('ormbackend.perms.entity.read')) {
            return true;
        }
        
        if ($permissions & config('ormbackend.perms.record.read')) {
            return $entity->getCreatedBy() && $entity->getCreatedBy()->getPrimary() === $userId;
        }

        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isUpdatingAllowed()
     */
    public function isUpdatingAllowed(?Entity $user, Entity $entity) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $classUrlName = Helper::classToUrl(get_class($entity));
        $permissions = $this->getUpdateAccess($classUrlName, $userId);

        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.update'));
        }
        
        if ($permissions & config('ormbackend.perms.entity.update')) {
            return true;
        }
        
        if ($permissions & config('ormbackend.perms.record.update')) {
            return $entity->getCreatedBy() && $entity->getCreatedBy()->getPrimary() === $userId;
        }

        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isDeletingAllowed()
     */
    public function isDeletingAllowed(?Entity $user, Entity $entity) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $classUrlName = Helper::classToUrl(get_class($entity));
        $permissions = $this->getDeleteAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.delete'));
        }
        
        if ($permissions & config('ormbackend.perms.entity.delete')) {
            return true;
        }
        
        if ($permissions & config('ormbackend.perms.record.delete')) {
            return $entity->getCreatedBy() && $entity->getCreatedBy()->getPrimary() === $userId;
        }
        
        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::isRestoringAllowed()
     */
    public function isRestoringAllowed(?Entity $user, Entity $entity) : bool
    {
        $userId = $user ? $user->getPrimary() : null;
        $classUrlName = Helper::classToUrl(get_class($entity));
        $permissions = $this->getRestoreAccess($classUrlName, $userId);
        
        if (!$permissions) {
            return false;
        }
        
        if (!$user) {
            return (bool) ($permissions & config('ormbackend.perms.guest.restore', 0));
        }
        
        if ($permissions & config('ormbackend.perms.entity.restore')) {
            return true;
        }
        
        if ($permissions & config('ormbackend.perms.record.restore')) {
            return $entity->getCreatedBy() && $entity->getCreatedBy()->getPrimary() === $userId;
        }
        
        return false;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \OrmBackend\ACL\AccessControl::addRecordsFilter()
     */
    public function addRecordsFilter(string $class, array $parameters = [], string $alias = null) : array
    {
        if ($this->isSuperAdmin(auth()->id())) {
            return $parameters;
        }
        
        $classUrlName = Helper::classToUrl($class);
        $permissions = $this->getReadAccess($classUrlName, auth()->id());

        if (!$permissions) {
            throw new AuthorizationException();
        }
        
        if ($permissions === config('ormbackend.perms.record.read')) {
            $reflectionClass = new \ReflectionClass($class);
            $alias = $alias ? $alias : lcfirst( $reflectionClass->getShortName() );
            
            if (!array_key_exists('filter', $parameters)) {
                $parameters['filter'] = [];
            }
            
            if ($reflectionClass->implementsInterface(Authenticatable::class)) {
                $parameters['filter'][] = [
                    $alias . '.' . $class::getIdentifierName(),
                    'eq',
                    Auth::id()
                ];
            } else {
                $parameters['filter'][] = [
                    $alias . '.createdBy.' . $class::getIdentifierName(),
                    'eq',
                    Auth::id()
                ];
            }
        }

        return $parameters;
    }

}
