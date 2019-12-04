<?php

namespace ItAces\Controllers;

use Doctrine\ORM\AbstractQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use ItAces\Repositories\Repository;
use ItAces\ORM\PaginatorAdapter;

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
     * @param \Doctrine\ORM\AbstractQuery $query
     * @param int           $perPage
     * @param string        $page
     * @param bool          $fetchJoinCollection
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(AbstractQuery $query, int $perPage = 15, string $pageName = 'page', bool $fetchJoinCollection = false) : LengthAwarePaginator
    {
        return PaginatorAdapter::fromRequest(
            $query,
            $perPage,
            $pageName,
            $fetchJoinCollection
        )->make();
    }
    
}
