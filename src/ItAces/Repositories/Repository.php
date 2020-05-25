<?php

namespace ItAces\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ItAces\SoftDeleteable;
use ItAces\ORM\DevelopmentException;
use ItAces\ORM\Orderly;
use ItAces\ORM\QueryFactory;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\View\FieldContainer;

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
        $this->enableCaches($query, $class);

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
        $this->enableCaches($query, $class);

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
        
        if (($entity instanceof SoftDeleteable) && config('itaces.softdelete', true)) {
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
        if ($id < 1) {
            abort(400);
        }
        
        /**
         * There is no way to use this repository method because the object will not be cached.
         * Since the presence of filtering options will disable caching.
         */
        $entity = $this->em()->getRepository($class)->find($id);
        
        if (!$entity) {
            abort(404);
        }

        Gate::authorize('read-record', $entity);
        
        return $entity;
    }
    
    /**
     * Creating instance and initializing it from the data array and persisting the transient instance on a session.
     * 
     * @param string $class
     * @param array $data
     * @param int $id
     * @throws DevelopmentException
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function createOrUpdate(string $class, array $data, int $id = null) : EntityBase
    {
        if ($id === null && isset($data['id'])) {
            $id = (int) $data['id'];
        }

        if (array_key_exists('id', $data)) {
            unset($data['id']); // Prevents setting ID value
        }

        if ($id) {
            if ($id < 1) {
                abort(400);
            }
            
            $entity = $this->findOrFail($class, $id);
            Gate::authorize('update-record', $entity);
        } else {
            $entity = new $class();
        }

        $this->initializeEntity($entity, $data);
        $this->initializeAssociations($entity, $data);
        
        if (!$id) {
            $this->em->persist($entity);
        }
        
        return $entity;
    }
    
    /**
     * Saving request data to a database. To update entities, it is necessary that the 'id'
     * field and its value be present in the data. The data format should look like this:
     * <code>
     * [
     *     'vendor-package-foo' => [
     *         'firstField' => value,
     *         'nextField' => value
     *     ],
     *     'vendor-package-bar' => [
     *         'firstField' => value,
     *         'nextField' => value
     *     ],
     *     'vendor-package-next_class' => [
     *         'firstField' => value,
     *         'nextField' => value
     *     ]
     * ]
     * </code>
     * 
     * @param string $classUrlName
     * @param array $data
     * @throws \Exception
     */
    public function saveContainer(array $data, string $classUrlName)
    {
        $storedFiles = [];
        $exception = null;
        $this->em()->beginTransaction();
        
        try {
            $map = FieldContainer::readRequest($data, $storedFiles);

            foreach ($map as $className => $data) {
                $classUrlName = Helper::classToUlr($className);
                Validator::make($data, $className::getRequestValidationRules())->validate();
                $this->createOrUpdate($className, $data);
            }
            
            $this->em()->flush();
            $this->em()->commit();
        } catch (ValidationException $e) {
            $messages = FieldContainer::exceptionToMessages($e, $classUrlName);
            $exception = ValidationException::withMessages($messages);
        } catch (\Exception $e) {
            $exception = $e;
        }
        
        if ($exception) {
            $this->em()->rollback();
            
            foreach ($storedFiles as $storedFile) {
                /**
                 *
                 * @var \ItAces\Types\FileType $file
                 */
                $file = $storedFile;
                Storage::delete($file->getPath());
            }
            
            throw $exception;
        }
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
        
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->addSelect('RAND() as HIDDEN rand')
            ->from($class, $alias)
            ->orderBy('rand')
            ->setMaxResults($limit)
            ->getQuery();
            
        
//         $max = $this->em->createQueryBuilder()
//             ->add('select', "MAX({$alias}.id)")
//             ->from($class, $alias)
//             ->getQuery()
//             ->getSingleScalarResult();
        
//         $ids = $this->em->createQueryBuilder()
//             ->add('select', 'CEIL(RAND() * :max) AS id')
//             ->from($class, $alias)
//             ->setParameter('max', $max, Types::INTEGER)
//             ->setMaxResults($limit)
//             ->getQuery()
//             ->getScalarResult();
//             dd($max);
//         array_walk($ids, function(&$item) {$item = (int) $item['id'];});
        
//         return $this->em->createQueryBuilder()
//             ->select($alias)
//             ->from($class, $alias)
//             ->where("{$alias}.id IN (:ids)")
//             ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
//             ->getQuery();
    }

    /**
     * Retrieving an array of objects using the WHERE IN clause.
     * 
     * @param string $class
     * @param int[] $ids
     * @return array
     */
    public function fetchCollection(string $class, array $ids) : array
    {
        $alias = lcfirst((new \ReflectionClass($class))->getShortName());
        
        return $this->getQuery($class, [
            'filter' => [
                [$alias.'.id', 'in', $ids]
            ]
        ], $alias)->getResult();
    }
    
    protected function createAssociation(string $class, array $data) : EntityBase
    {
        $entity = new $class();
        $this->initializeEntity($entity, $data);
        
        return $entity;
    }
    
    protected function createCollection(string $class, array $data) : array
    {
        $collection = [];
        
        foreach ($data as $value) {
            $collection[] = $this->createAssociation($class, $value);
        }
        
        return $collection;
    }
    
    protected function appendAdditionalParameters(string $class, array $parameters = [], string $alias = null) : array
    {
        return $parameters;
    }
    
    protected function initializeEntity(EntityBase &$entity, array $data) : void
    {
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];
            
            if (array_key_exists($fieldName, $data)) {
                $value = $this->orderly->sanitizeString($fieldMapping, $data[$fieldName]);
                $setter = 'set' . ucfirst($fieldName);
                $entity->$setter( $value );
            }
        }
    }
    
    protected function initializeAssociations(EntityBase &$entity, array $data) : void
    {
        $classMetadata = $this->em->getClassMetadata(get_class($entity));

        foreach ($classMetadata->associationMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];
            
            if (!array_key_exists($fieldName, $data)) {
                continue;
            }
            
            $value = $data[$fieldName];
            $targetEntity = $fieldMapping['targetEntity'];
            $getter = 'get' . ucfirst($fieldName);
            $setter = 'set' . ucfirst($fieldName);
            
            if ($fieldMapping['type'] & ClassMetadataInfo::TO_MANY) {
                if (!is_array($value)) {
                    throw new DevelopmentException('The parameter value for multiple association must be an array.');
                }
                
                /**
                 *
                 * @var \Doctrine\Common\Collections\Collection $collection
                 */
                $collection = $entity->$getter();
                $collection->clear();
                
                if ($value) {
                    $associations = [];
                    $existing = [];
                    $posted = [];
                    
                    foreach ($value as $association) {
                        if ($association instanceof EntityBase) {
                            $associations[] = $association;
                        } else if (is_numeric($association)) {
                            $id = (int) $association;
                            
                            /**
                             * A multiple select value is not present in the http request unless it has the selected values.
                             */
                            if ($id > 0) {
                                $existing[] = (int) $association;
                            }
                        } else {
                            $posted[] = $association;
                        }
                    }
                    
                    if ($existing) {
                        $associations = array_merge($associations, $this->fetchCollection($targetEntity, $existing));
                    }
                    
                    if ($posted) {
                        $associations = array_merge($associations, $this->createCollection($targetEntity, $posted));
                    }
                    
                    foreach ($associations as $association) {
                        $collection->add($association);
                    }
                }
            } else if ($fieldMapping['type'] & ClassMetadataInfo::TO_ONE) {
                if ($value === null || ($value instanceof EntityBase)) {
                    $association = $value;
                } else if (is_numeric($value)) {
                    $association = $this->findOrFail($targetEntity, $value);
                } else {
                    $association = $this->createAssociation($targetEntity, $value);
                }
                
                $entity->$setter( $association );
            }
        }
    }

    protected function enableCaches(Query &$query, string $class)
    {
        /**
         * Turn on the 2nd cache only if there are no filtering options.
         */
        if ($query->getAST()->whereClause) {
            // app_model_eventtile timestamp_cache_region query_cache_region
            $query->enableResultCache(config('itaces.caches.result_ttl'));
        } else if ($this->em->getConfiguration()->isSecondLevelCacheEnabled()) {
            $query->setLifetime( $this->em->getConfiguration()->getSecondLevelCacheConfiguration()->getRegionsConfiguration()->getDefaultLifetime() );
            $query->setCacheable(true);
        }
    }

}
