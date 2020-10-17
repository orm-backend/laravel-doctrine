<?php

namespace OrmBackend\Controllers;

use Illuminate\Http\Request;
use OrmBackend\Publishable;
use OrmBackend\Json\JsonCollectionSerializer;
use OrmBackend\Json\JsonSerializer;
use OrmBackend\Repositories\WithJoinsRepository;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class JsonCRUDController extends WebController
{
    /**
     * 
     * @var string
     */
    protected $class;
    
//     /**
//      *
//      * @var \OrmBackend\Repositories\WithJoinsRepository
//      */
//     protected $withJoins;
    
    public function __construct(bool $cacheable = false)
    {
        parent::__construct($cacheable);
        //$this->withJoins = new WithJoinsRepository(true, $cacheable);
    }
    
    /**
    *
    * @param  \Illuminate\Http\Request  $request
    * @return  \Illuminate\Http\JsonResponse
    */
    public function search(Request $request)
    {
        $paginator = $this->cursor($this->repository->createQuery($this->class))->appends($request->all());

        return response()->json( new JsonCollectionSerializer($this->repository->em(), $paginator), 200);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->json()->all();
        $request->validate($this->class::getRequestValidationRules());
        $instance = $this->repository->createOrUpdate($this->class, $data);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance), 201);
    }
    
    /**
     *
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(int $id)
    {
        $instance = $this->repository->findOrFail($this->class, $id);
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance), 200);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $data = $request->json()->all();
        $request->validate($this->class::getRequestValidationRules());
        $instance = $this->repository->createOrUpdate($this->class, $data, $id);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance), 200);
    }
    
    /**
     *
     * @param  integer  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $id)
    {
        $this->repository->delete($this->class, $id);
        $this->repository->em()->flush();
        
        return response()->json(null, 204);
    }
    
    /**
     * 
     * @param string $class
     * @return \OrmBackend\Controllers\JsonCRUDController
     */
    public function setClass(string $class)
    {
        if (!(new \ReflectionClass($class))->implementsInterface(Publishable::class)) {
            abort(403);
        }
        
        $this->class = $class;
        
        return $this;
    }
    
}
