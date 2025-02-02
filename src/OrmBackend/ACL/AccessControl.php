<?php
namespace OrmBackend\ACL;

use OrmBackend\ORM\Entities\Entity;


/**
 * The main point of ACL, fully integrated with the package code. You must write your own
 * implementation of this interface or just override the default.
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
interface AccessControl
{
    /**
     * Is the current user a super administrator or not?
     *
     * @param int $userId
     * @return bool
     */
    public function isSuperAdmin(int $userId = null) : bool;
    
    /**
     * Does the current user or guest have any write permissions or not? For example,
     * if the specified entity is allowed to create a record for a guest or a given user,
     * the method should return true.
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyCreatingAllowed(?Entity $user, string $classUrlName) : bool;
    
    /**
     * Does the current user or guest have any read permissions or not? For example,
     * If a guest or a given user can read at least one record of the specified entity,
     * the method should return true.
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyReadingAllowed(?Entity $user, string $classUrlName) : bool;
    
    /**
     * Does the current user or guest have any update permissions or not? For example,
     * If a guest or a given user can update at least one record of the specified entity,
     * the method should return true.
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyUpdatingAllowed(?Entity $user, string $classUrlName) : bool;
    
    /**
     * Does the current user or guest have any delete permissions or not? For example,
     * If a guest or a given user can delete at least one record of the specified entity,
     * the method should return true.
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyDeletingAllowed(?Entity $user, string $classUrlName) : bool;
    
    /**
     * Does the current user or guest have any restore permissions or not? For example,
     * If a guest or a given user can restore at least one record of the specified entity,
     * the method should return true.
     * 
     * @param Entity $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyRestoringAllowed(?Entity $user, string $classUrlName) : bool;
    
    /**
     * Can the current user or guest read the specified object or not?
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isReadingAllowed(?Entity $user, Entity $entity) : bool;
    
    /**
     * Can the current user or guest update the specified object or not?
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isUpdatingAllowed(?Entity $user, Entity $entity) : bool;
    
    /**
     * Can the current user or guest delete the specified object or not?
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isDeletingAllowed(?Entity $user, Entity $entity) : bool;
    
    /**
     * Can the current user or guest restore the specified object or not?
     * 
     * @param Entity $user
     * @param Entity $entity
     * @return bool
     */
    public function isRestoringAllowed(?Entity $user, Entity $entity) : bool;
    
    /**
     * If not all objects of the specified entity are readable by the current user or guest,
     * the method should add a filter to the parameters and then return it.
     * 
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return array
     */
    public function addRecordsFilter(string $class, array $parameters = [], string $alias = null) : array;

}
