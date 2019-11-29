<?php

namespace ItAces\ORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelDoctrine\ORM\Pagination\PaginatorAdapter;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
abstract class Repository
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
     * @return \LaravelDoctrine\ORM\Facades\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
    
    
    /**
     * 
     * @param array[] $parameters
     * @param int $perPage
     * @param string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\ItAces\ORM\Entities\EntityBase[]
     */
    abstract public function getList(array $parameters = [], int $perPage = 15, string $pageName = 'page');
    
    /**
     * 
     * @param int $id
     * @return \ItAces\Entity
     */
    abstract public function findOrFail(int $id);
    
    /**
     * @return string[]
     */
    abstract protected function getDefaultOreder();

    
    /**
     * @param AbstractQuery $query
     * @param int           $perPage
     * @param string        $page
     * @param bool          $fetchJoinCollection
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(AbstractQuery $query, int $perPage, string $pageName = 'page', bool $fetchJoinCollection = null) : LengthAwarePaginator
    {
        return PaginatorAdapter::fromRequest(
            $query,
            $perPage,
            $pageName,
            $fetchJoinCollection === true
        )->make();
    }
    
    /**
     * 
     * @param string $class
     * @param array $parameters
     * @param string $alias
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder(string $class, array $parameters = [], string $alias = null) : QueryBuilder
    {
        if (!array_key_exists('order', $parameters)) {
            //$parameters['order'] = $this->getDefaultOreder();
        }
 
        return Query::fromArray($this->em, $class, $parameters, $alias)->createQueryBuilder();
    }
    
}
