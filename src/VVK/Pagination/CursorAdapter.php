<?php

namespace VVK\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Illuminate\Pagination\Paginator;

class CursorAdapter
{
    /**
     * @var \Doctrine\ORM\Query
     */
    protected $query;

    /**
     * @var int
     */
    private $perPage;

    /**
     * @var callable
     */
    private $pageResolver;
    
    /**
     * @var boolean
     */
    private $fetchJoinCollection;

    /**
     * @param \Doctrine\ORM\Query $query
     * @param int           $perPage
     * @param callable      $pageResolver
     * @param bool $fetchJoinCollection
     */
    private function __construct(Query $query, $perPage, $pageResolver, bool $fetchJoinCollection = false)
    {
        $this->query               = $query;
        $this->perPage             = $perPage;
        $this->pageResolver        = $pageResolver;
        $this->fetchJoinCollection     = $fetchJoinCollection;
    }

    /**
     * @param \Doctrine\ORM\Query $query
     * @param int           $perPage
     * @param string        $pageName
     * @param bool $fetchJoinCollection
     * @return PaginatorAdapter
     */
    public static function fromRequest(Query $query, $perPage = 20, $pageName = 'page', bool $fetchJoinCollection = false)
    {
        return new static(
            $query,
            $perPage,
            function () use ($pageName) {
                return Paginator::resolveCurrentPage($pageName);
            },
            $fetchJoinCollection
        );
    }

    /**
     * @param \Doctrine\ORM\Query $query
     * @param int           $perPage
     * @param int           $page
     * @param bool $fetchJoinCollection
     * @return PaginatorAdapter
     */
    public static function fromParams(Query $query, $perPage = 20, $page = 1, bool $fetchJoinCollection = false)
    {
        return new static(
            $query,
            $perPage,
            function () use ($page) {
                return $page;
            },
            $fetchJoinCollection
        );
    }

    public function make()
    {
        $page = $this->getCurrentPage();

        $this->query($this->query)
             ->skip($this->getSkipAmount($this->perPage, $page))
             ->take($this->perPage + 1);

        return $this->convertToLaravelPaginator(
            $this->getDoctrinePaginator(),
            $this->perPage,
            $page
        );
    }

    /**
     * @param \Doctrine\ORM\Query $query
     *
     * @return $this
     */
    protected function query(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param int $start
     *
     * @return $this
     */
    protected function skip($start)
    {
        $this->getQuery()->setFirstResult($start);

        return $this;
    }

    /**
     * @param int $perPage
     *
     * @return $this
     */
    protected function take($perPage)
    {
        $this->getQuery()->setMaxResults($perPage);

        return $this;
    }

    /**
     * @param int $perPage
     * @param int $page
     *
     * @return int
     */
    protected function getSkipAmount($perPage, $page)
    {
        return ($page - 1) * $perPage;
    }

    /**
     * @return DoctrinePaginator
     */
    private function getDoctrinePaginator()
    {
        $paginator = new DoctrinePaginator($this->getQuery(), $this->fetchJoinCollection);
        
        if (!$this->fetchJoinCollection) {
            $paginator->setUseOutputWalkers(false);
        }
        
        return $paginator;
    }

    /**
     * @param DoctrinePaginator $doctrinePaginator
     * @param int               $perPage
     * @param int               $page
     *
     * @return \Illuminate\Pagination\Paginator
     */
    protected function convertToLaravelPaginator(DoctrinePaginator $doctrinePaginator, $perPage, $page)
    {
        $results     = iterator_to_array($doctrinePaginator);
        $path        = Paginator::resolveCurrentPath();

        return new Paginator(
            $results,
            $perPage,
            $page,
            compact('path')
        );
    }

    /**
     * @return int
     */
    protected function getCurrentPage()
    {
        $page = call_user_func($this->pageResolver);

        return $page > 0 ? $page : 1;
    }
}
