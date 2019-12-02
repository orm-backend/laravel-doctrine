<?php

namespace ItAces\ORM;

use Doctrine\ORM\EntityManager;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class QueryFactory
{
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @param boolean $fetchAssociations
     * @return \ItAces\ORM\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $parameters = [], string $alias = null, bool $fetchAssociations = null) : Query
    {
        $alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
        $select = array_key_exists('select', $parameters) ? $parameters['select'] : [];
        $join = array_key_exists('join', $parameters) ? $parameters['join'] : [];
        $filter = array_key_exists('filter', $parameters) ? $parameters['filter'] : [];
        $order = array_key_exists('order', $parameters) ? $parameters['order'] : [];

        if (!in_array($alias, $select)) {
            $select[] = $alias;
        }
        
        if ($fetchAssociations) {
            $classMetadata = $em->getClassMetadata($class);
            
            foreach ($classMetadata->associationMappings as $associationMapping) {
                $fieldName = $associationMapping['fieldName'];
                $select[] = "{$alias}.{$fieldName}";
            }
        }
        
        $qb = $em->createQueryBuilder()->from($class, $alias);
        $helper = new QueryHelper();
        $query = new Query();
        $query->setAlias($alias);
        $query->setQueryBuilder($qb);
        $query->setSelect($select);
        $query->setJoin($join);
        $query->setFilter($filter);
        $query->setOrder($order);
        $query->setHelper($helper);
        $query->setValidator(new QueryValidator($qb, $helper, $alias, $class));
        $query->setBuilder(new ParameterBuilder($qb, $helper, $alias, $class));
        
        return $query;
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $json
     * @throws \ItAces\ORM\DevelopmentException
     * @return \ItAces\ORM\Query
     */
    public static function fromJson(EntityManager $em, string $class, string $json) : Query
    {
        $parameters = json_decode($json, true, null, JSON_BIGINT_AS_STRING);
        
        if ($parameters === null) {
            throw new DevelopmentException('The json cannot be decoded or the encoded data is deeper than the recursion limit.');
        }
        
        return static::fromArray($em, $class, $parameters);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @return \ItAces\ORM\Query
     */
    public static function fromRequest(EntityManager $em, string $class) : Query
    {
        if (request()->isMethod('GET')) {
            return static::fromGet($em, $class);
        } else if (request()->isMethod('PUT')) {
            return static::fromPut($em, $class);
        } else if (request()->isMethod('POST')) {
            return static::fromPost($em, $class);
        }
        
        return static::fromArray($em, $class, request()->all(), null, true);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @return \ItAces\ORM\Query
     */
    public static function fromGet(EntityManager $em, string $class) : Query
    {
        return static::fromArray($em, $class, request()->query(), null, true);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @return \ItAces\ORM\Query
     */
    public static function fromPost(EntityManager $em, string $class) : Query
    {
        return static::fromArray($em, $class, request()->post(), null, true);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @return \ItAces\ORM\Query
     */
    public static function fromPut(EntityManager $em, string $class) : Query
    {
        return static::fromJson($em, $class, request()->json(), null, true);
    }
    
}
