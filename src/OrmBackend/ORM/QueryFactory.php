<?php

namespace OrmBackend\ORM;

use Doctrine\ORM\EntityManager;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class QueryFactory
{
    
    /**
     * @var array
     */
    private static $select;
    
    /**
     * 
     * @return array
     */
    public static function lastSelect()
    {
        return self::$select;
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return \OrmBackend\ORM\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $parameters = [], string $alias = null) : Query
    {
        $alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
        $select = array_key_exists('select', $parameters) ? $parameters['select'] : [];
        $join = array_key_exists('join', $parameters) ? $parameters['join'] : [];
        $filter = array_key_exists('filter', $parameters) ? $parameters['filter'] : [];
        $order = array_key_exists('order', $parameters) ? $parameters['order'] : [];

        if (!in_array($alias, $select)) {
            $select[] = $alias;
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
        self::$select = $select;
        
        return $query;
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $json
     * @param array $additionalParameters
     * @param string $alias
     * @throws \OrmBackend\ORM\DevelopmentException
     * @return \OrmBackend\ORM\Query
     */
    public static function fromJson(EntityManager $em, string $class, string $json, array $additionalParameters = [], string $alias = null) : Query
    {
        $parameters = json_decode($json, true, null, JSON_BIGINT_AS_STRING);
        
        if ($parameters === null) {
            throw new DevelopmentException('The json cannot be decoded or the encoded data is deeper than the recursion limit.');
        }
        
        return static::fromArray($em, $class, array_merge($parameters, $additionalParameters), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $additionalParameters
     * @param string $alias
     * @return \OrmBackend\ORM\Query
     */
    public static function fromRequest(EntityManager $em, string $class, array $additionalParameters = [], string $alias = null) : Query
    {
        if (request()->isMethod('GET')) {
            return static::fromGet($em, $class, $additionalParameters, $alias);
        } else if (request()->isMethod('PUT')) {
            return static::fromPut($em, $class, $additionalParameters, $alias);
        } else if (request()->isMethod('POST')) {
            return static::fromPost($em, $class, $additionalParameters, $alias);
        }
        
        return static::fromArray($em, $class, array_merge_recursive(request()->all(), $additionalParameters), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $additionalParameters
     * @param string $alias
     * @return \OrmBackend\ORM\Query
     */
    public static function fromGet(EntityManager $em, string $class, array $additionalParameters = [], string $alias = null) : Query
    {
        return static::fromArray($em, $class, array_merge_recursive(request()->query(), $additionalParameters), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $additionalParameters
     * @param string $alias
     * @return \OrmBackend\ORM\Query
     */
    public static function fromPost(EntityManager $em, string $class, array $additionalParameters = [], string $alias = null) : Query
    {
        return static::fromArray($em, $class, array_merge_recursive(request()->post(), $additionalParameters), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param array $additionalParameters
     * @param string $alias
     * @return \OrmBackend\ORM\Query
     */
    public static function fromPut(EntityManager $em, string $class, array $additionalParameters = [], string $alias = null) : Query
    {
        return static::fromArray($em, $class, array_merge_recursive(request()->json()->all(), $additionalParameters), $alias);
    }
    
}
