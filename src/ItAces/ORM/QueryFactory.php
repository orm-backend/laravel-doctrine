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
     * @return \ItAces\ORM\Query
     */
    public static function fromArray(EntityManager $em, string $class, array $parameters = [], string $alias = null) : Query
    {
        $instance = new Query();
        $instance->alias = $alias ? $alias : lcfirst( (new \ReflectionClass($class))->getShortName() );
        $instance->qb = $em->createQueryBuilder()->from($class, $instance->alias);
        $instance->select = array_key_exists('select', $parameters) ? $parameters['select'] : [];
        $instance->join = array_key_exists('join', $parameters) ? $parameters['join'] : [];
        $instance->filter = array_key_exists('filter', $parameters) ? $parameters['filter'] : [];
        $instance->order = array_key_exists('order', $parameters) ? $parameters['order'] : [];
        
        if (!in_array($instance->alias, $instance->select)) {
            $instance->select[] = $instance->alias;
        }
        
        $instance->helper = new QueryHelper();
        $instance->validator = new QueryValidator($instance->qb, $instance->helper, $instance->alias, $class);
        $instance->builder = new ParameterBuilder($instance->qb, $instance->helper, $instance->alias, $class);
        
        return $instance;
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $json
     * @param string $alias
     * @throws \ItAces\ORM\DevelopmentException
     * @return \ItAces\ORM\Query
     */
    public static function fromJson(EntityManager $em, string $class, string $json, string $alias = null) : Query
    {
        $parameters = json_decode($json, true);
        
        if ($parameters === null) {
            throw new DevelopmentException('The json cannot be decoded or the encoded data is deeper than the recursion limit.');
        }
        
        return static::fromArray($em, $class, $parameters, $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $alias
     * @return \ItAces\ORM\Query
     */
    public static function fromRequest(EntityManager $em, string $class, string $alias = null) : Query
    {
        return static::fromArray($em, $class, request()->all(), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $alias
     * @return \ItAces\ORM\Query
     */
    public static function fromGet(EntityManager $em, string $class, string $alias = null) : Query
    {
        return static::fromArray($em, $class, request()->query(), $alias);
    }
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     * @param string $alias
     * @return \ItAces\ORM\Query
     */
    public static function fromPost(EntityManager $em, string $class, string $alias = null) : Query
    {
        return static::fromArray($em, $class, request()->post(), $alias);
    }
    
}
