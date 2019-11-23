<?php

namespace ItAces\Api;

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
     * @param int $id
     * @return \ItAces\Entity
     */
    abstract public function findOrFail(int $id);
    
    
    /**
     * @param AbstractQuery $query
     * @param int           $perPage
     * @param int           $page
     * @param bool          $fetchJoinCollection
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(AbstractQuery $query, int $perPage, int $page = 1, bool $fetchJoinCollection = null) : LengthAwarePaginator
    {
        return PaginatorAdapter::fromParams(
            $query,
            $perPage,
            $page,
            $fetchJoinCollection === true
        )->make();
    }
    
    /**
     * 
     * @param string $class
     * @param array $select
     * @param array $filter
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder(string $class, array $select, array $filter) : QueryBuilder
    {
        return Query::fromArray($this->em, $class, $select, $filter)->createQueryBuilder();
    }
    
}
