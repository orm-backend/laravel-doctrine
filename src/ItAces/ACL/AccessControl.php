<?php
namespace ItAces\ACL;

use ItAces\ORM\Entities\EntityBase;


/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
interface AccessControl
{
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyCreatingAllowed(?EntityBase $user, string $classUrlName) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyReadingAllowed(?EntityBase $user, string $classUrlName) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyUpdatingAllowed(?EntityBase $user, string $classUrlName) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyDeletingAllowed(?EntityBase $user, string $classUrlName) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param string $classUrlName
     * @return bool
     */
    public function isAnyRestoringAllowed(?EntityBase $user, string $classUrlName) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isReadingAllowed(?EntityBase $user, EntityBase $entity) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isUpdatingAllowed(?EntityBase $user, EntityBase $entity) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isDeletingAllowed(?EntityBase $user, EntityBase $entity) : bool;
    
    /**
     * 
     * @param EntityBase $user
     * @param EntityBase $entity
     * @return bool
     */
    public function isRestoringAllowed(?EntityBase $user, EntityBase $entity) : bool;
    
    /**
     * 
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return array
     */
    public function addRecordsFilter(string $class, array $parameters = [], string $alias = null) : array;

}
