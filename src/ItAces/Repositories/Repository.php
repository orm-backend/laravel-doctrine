<?php

namespace ItAces\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use ItAces\SoftDeleteable;
use ItAces\ORM\DevelopmentException;
use ItAces\ORM\Orderly;
use ItAces\ORM\QueryFactory;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;

/**
 * This repository does not join any data from related entities.
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Repository
{
    /**
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     *
     * @var \ItAces\ACL\AccessControl $acl
     */
    protected $acl;
    
    /**
     * 
     * @var \ItAces\ORM\Orderly
     */
    protected $orderly;
    

    public function __construct() {
        $this->em = app('em');
        $this->acl = app('acl');
        $this->orderly = new Orderly;
    }
    
    /**
     *
     * @return  \Doctrine\ORM\EntityManager
     */
    public function em() : EntityManager
    {
        return $this->em;
    }
    
    /**
     * Builds query from method parameters.
     * 
     * @param string $class
     * @param array[] $parameters
     * @param string $alias
     * @return \Doctrine\ORM\Query
     */
    public function getQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $parameters = $this->acl->addRecordsFilter($class, $parameters, $alias);
        $query = QueryFactory::fromArray($this->em, $class, $parameters, $alias)->createQueryBuilder()->getQuery();
        $this->enableCaches($query);

        return $query;
    }
    
    /**
     * Builds query from request and method parameters.
     *
     * @param string $class
     * @param array[] $parameters
     * @param string $alias
     * @return \Doctrine\ORM\Query
     */
    public function createQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $parameters = $this->acl->addRecordsFilter($class, $parameters, $alias);
        $query = QueryFactory::fromRequest($this->em, $class, $parameters, $alias)->createQueryBuilder()->getQuery();
        $this->enableCaches($query);

        return $query;
    }
    
    /**
     *
     * @param string $class
     * @param integer $id
     */
    public function delete(string $class, int $id) : void
    {
        $entity = $this->findOrFail($class, $id);
        Gate::authorize('delete-record', $entity);
        
        if ($entity instanceof SoftDeleteable) {
            /**
             *
             * @var \ItAces\SoftDeleteable $object
             */
            $deleteable = $entity;
            $deleteable->setDeletedAt(now());
            
            if (Auth::id()) {
                $deleteable->setDeletedBy(Auth::user());
            }
        } else {
            $this->em->remove($entity);
        }
    }
    
    /**
     *
     * @param string $class
     * @param integer $id
     */
    public function restore(string $class, int $id) : void
    {
        $entity = $this->findOrFail($class, $id);
        Gate::authorize('restore-record', $entity);
        
        if ($entity instanceof SoftDeleteable) {
            /**
             *
             * @var \ItAces\SoftDeleteable $object
             */
            $deleteable = $entity;
            $deleteable->setDeletedAt(null);
            $deleteable->setDeletedBy(null);
        }
    }

    /**
     *
     * @param string $class
     * @param int $id
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function findOrFail(string $class, int $id) : EntityBase
    {
        $alias = lcfirst( (new \ReflectionClass($class))->getShortName() );
        $parameters = [
            'filter' => [
                [$alias.'.id', 'eq', $id]
            ]
        ];
        
        $parameters = $this->appendAdditionalParameters($class, $parameters, $alias);
        $entity = $this->getQuery($class, $parameters, $alias)->getSingleResult();
        
        if (!$entity) {
            abort(404, 'Not found.');
        }
        
        Gate::authorize('read-record', $entity);
        
        return $entity;
    }
    
    /**
     * 
     * @param string $class
     * @param array $data
     * @param int $id
     * @throws DevelopmentException
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function createOrUpdate(string $class, array $data, int $id = null) : EntityBase
    {
        $classUrlName = Helper::classToUlr($class);

        if ($id) {
            $entity = $this->findOrFail($class, $id);
            Gate::authorize('update-record', $entity);
        } else {
            $entity = new $class();
        }

        $classMetadata = $this->em->getClassMetadata($class);

        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];

            if (array_key_exists($fieldName, $data)) {
                $setter = 'set' . ucfirst($fieldName);
                $entity->$setter( $this->orderly->sanitizeString($fieldMapping, $data[$fieldName]) );
            }
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];
            
            if (array_key_exists($fieldName, $data)) {
                $targetEntity = $associationMapping['targetEntity'];
                $getter = 'get' . ucfirst($fieldName);
                $setter = 'set' . ucfirst($fieldName);
                
                if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                    // TODO cascade: persist
                    if (!is_array($data[$fieldName])) {
                        throw new DevelopmentException('The parameter value for multiple associations must be an array.');
                    }

                    $collection = $entity->$getter();
                    $collection->clear();
                    // TODO DevelopmentException or ValidationException ?
                    foreach ($data[$fieldName] as $associationId) {
                        $associationId = (int) $associationId;
                        
                        if ($associationId < 1) {
                            throw new DevelopmentException("The value of '{$class}::{$fieldName}' must be an array of unsigned integer.");
                        }
                        
                        $association = $this->em->find($targetEntity, $associationId);
                        
                        if (!$association) {
                            throw new DevelopmentException("The value of '{$class}::{$fieldName}' is incorrect. The entity '{$targetEntity}' with id '{$associationId}' does not exists.");
                        }
                        
                        $collection->add($association);
                    }
                } else if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {

                    if ($data[$fieldName] !== null) {
                        if ($data[$fieldName] instanceof EntityBase) {
                            $association = $data[$fieldName];
                        } else {
                            $associationId = (int) $data[$fieldName];
                            
                            if ($associationId < 1) {
                                throw new DevelopmentException("The value of '{$class}::{$fieldName}' must be an unsigned integer.");
                            }
                            
                            $association = $this->em->find($targetEntity, $associationId);
                            
                            if (!$association) {
                                throw new DevelopmentException("The value of '{$class}::{$fieldName}' is incorrect. The entity '{$targetEntity}' with id '{$associationId}' does not exists.");
                            }
                        }
                        
                        $entity->$setter( $association );
                    } else {
                        $entity->$setter( $data[$fieldName] );
                    }
                }
            }
        }
        
        if (!$id) {
            $this->em->persist($entity);
        }
        
        return $entity;
    }
    
    /**
     *
     * @param string $class
     * @param integer $limit
     * @return \Doctrine\ORM\Query
     */
    public function random(string $class, int $limit = 1) : Query
    {
        $alias = lcfirst( (new \ReflectionClass($class))->getShortName() );
        
        $max = $this->em->createQueryBuilder()
            ->add('select', "MAX({$alias}.id)")
            ->from($class, $alias)
            ->getQuery()
            ->getSingleScalarResult();
        
        $ids = $this->em->createQueryBuilder()
            ->add('select', 'CEIL(RAND() * :max) AS id')
            ->from($class, $alias)
            ->setParameter('max', $max, Types::INTEGER)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();
        
        array_walk($ids, function(&$item) {$item = (int) $item['id'];});
        
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($class, $alias)
            ->where("{$alias}.id IN (:ids)")
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery();
    }
    
    protected function appendAdditionalParameters(string $class, array $parameters = [], string $alias = null) : array
    {
        return $parameters;
    }

    protected function enableCaches(Query &$query)
    {
        if (config('itaces.caches.enabled')) {
            // Second level cache
            if (config('doctrine.cache.second_level')) {
                $query->disableResultCache();
                $query->setLifetime( config('itaces.caches.second_ttl') );
                $query->setCacheable(true);
            } else {
                $query->enableResultCache( config('itaces.caches.result_ttl') );
            }
        
            // SQL cache
            $query->setQueryCacheLifetime( config('itaces.caches.query_ttl') );
            $query->useQueryCache(true);
        } else {
            $query->useQueryCache(false);
            $query->setCacheable(false);
            $query->disableResultCache();
        }
    }

}
