<?php

namespace ItAces\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ItAces\ORM\DevelopmentException;
use ItAces\ORM\Orderly;
use ItAces\ORM\QueryFactory;
use ItAces\ORM\Entities\EntityBase;

/**
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
     * @var \ItAces\ORM\Orderly
     */
    protected $orderly;
    

    public function __construct() {
        $this->em = app('em');
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
     * 
     * @param string $class
     * @param array[] $parameters
     * @param string $alias
     * @return \Doctrine\ORM\Query
     */
    public function getQuery(string $class, array $parameters = [], string $alias = null) : Query
    {
        $query = QueryFactory::fromArray($this->em, $class, $parameters, $alias)->createQueryBuilder()->getQuery();
        
        if (config('doctrine.cache.second_level')) {
            $query->setCacheable(true);
        }
        
        return $query;
    }
    
    /**
     *
     * @param string $class
     * @param array[] $additionalParameters
     * @return \Doctrine\ORM\Query
     */
    public function createQuery(string $class, array $additionalParameters = []) : Query
    {
        $query = QueryFactory::fromRequest($this->em, $class, $additionalParameters)->createQueryBuilder()->getQuery();
        
        if (config('doctrine.cache.second_level')) {
            $query->setCacheable(true);
        }
        
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
        $this->em->remove($entity);
    }
    
    /**
     * 
     * @param string $class
     * @param int $id
     * @return \ItAces\ORM\Entities\EntityBase
     */
    public function findOrFail(string $class, int $id) : EntityBase
    {
        $element = $this->em->getRepository($class)->find($id);
        
        if (!$element) {
            abort(404, 'Not found.');
        }
        
        return $element;
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
        if ($id) {
            $entity = $this->findOrFail($class, $id);
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
                        $associationId = (int) $data[$fieldName];
                        
                        if ($associationId < 1) {
                            throw new DevelopmentException("The value of '{$class}::{$fieldName}' must be an unsigned integer.");
                        }
                        
                        $association = $this->em->find($targetEntity, $associationId);
                        
                        if (!$association) {
                            throw new DevelopmentException("The value of '{$class}::{$fieldName}' is incorrect. The entity '{$targetEntity}' with id '{$associationId}' does not exists.");
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

}
