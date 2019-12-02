<?php

namespace ItAces\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ItAces\ORM\DevelopmentException;
use ItAces\ORM\QueryFactory;

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
    
    public function __construct() {
        $this->em = app('em');
    }
    
    /**
     *
     * @return  \Doctrine\ORM\EntityManager
     */
    public function em()
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
        return QueryFactory::fromArray($this->em, $class, $parameters, $alias)->createQueryBuilder()->getQuery();
    }
    
    /**
     *
     * @param string $class
     * @return \Doctrine\ORM\Query
     */
    public function createQuery(string $class) : Query
    {
        return QueryFactory::fromRequest($this->em, $class)->createQueryBuilder()->getQuery();
    }
    
    /**
     *
     * @param string $class
     * @param integer $id
     * @return \Doctrine\ORM\Query
     */
    public function delete(string $class, int $id) : Query
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
    public function findOrFail(string $class, int $id) {
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
    public function createOrUpdate(string $class, array $data, int $id = null)
    {
        if ($id) {
            $entity = $this->findOrFail($class, $id);
        } else {
            $entity = new $class();
        }

        $classMetadata = $this->em->getClassMetadata($class);
        
        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $fieldName = $fieldMapping['fieldName'];
            $setter = 'set' . ucfirst($fieldName);
            
            if (array_key_exists($fieldName, $data)) {
                $entity->$setter( $data[$fieldName] );
            }
        }
        
        foreach ($classMetadata->associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];

            if (array_key_exists($fieldName, $data)) {
                $entityName = $associationMapping['targetEntity'];
                $getter = 'get' . ucfirst($fieldName);
                $setter = 'set' . ucfirst($fieldName);
                
                if ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                    // TODO cascade: persist
                    if (!is_array($data[$fieldName])) {
                        throw new DevelopmentException('The parameter for multiple associations must be an array.');
                    }

                    $collection = $entity->$getter();
                    $collection->clear();
                    
                    foreach ($data[$fieldName] as $associationId) {
                        $association = $this->em->find($entityName, $associationId);
                        
                        if (!$association) {
                            throw new DevelopmentException("Entity '{$entityName}' with id '{$associationId}' does not exists.");
                        }
                        
                        $collection->add($association);
                    }
                } else if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                    $association = $this->em->find($entityName, $data[$fieldName]);
                    
                    if (!$association) {
                        throw new DevelopmentException("Entity '{$entityName}' with id '{$associationId}' does not exists.");
                    }
                    
                    $entity->$setter( $association );
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
