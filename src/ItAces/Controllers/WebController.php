<?php

namespace ItAces\Controllers;

use Doctrine\ORM\Query;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use ItAces\Pagination\CursorAdapter;
use ItAces\Pagination\PaginatorAdapter;
use ItAces\Repositories\Repository;

abstract class WebController extends Controller
{
    
    /**
     *
     * @var \ItAces\Repositories\Repository
     */
    protected $repository;
    
    public function __construct()
    {
        $this->repository = new Repository;
        
        if (auth()->id() && auth()->user()->isAdmin()) {
            $this->repository->em()->getFilters()->disable('softdelete');
        }
    }
    
    /**
     * @param \Doctrine\ORM\Query $query
     * @param int           $perPage
     * @param string        $page
     * @param bool          $fetchJoinCollection
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(Query $query, int $perPage = 20, string $pageName = 'page', bool $fetchJoinCollection = false) : LengthAwarePaginator
    {
        return PaginatorAdapter::fromRequest(
            $query,
            request()->get('perpage', $perPage),
            $pageName,
            $fetchJoinCollection
        )->make();
    }
    
    /**
     * @param \Doctrine\ORM\Query $query
     * @param int           $perPage
     * @param string        $page
     * @param bool          $fetchJoinCollection
     * @return \Illuminate\Pagination\Paginator
     */
    protected function cursor(Query $query, int $perPage = 20, string $pageName = 'page', bool $fetchJoinCollection = false) : Paginator
    {
        return CursorAdapter::fromRequest(
            $query,
            request()->get('perpage', $perPage),
            $pageName,
            $fetchJoinCollection
        )->make();
    }
    
}
