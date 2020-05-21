<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use ItAces\Json\JsonCollectionSerializer;
use ItAces\Json\JsonSerializer;
use ItAces\Repositories\WithJoinsRepository;

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
    
    /**
     *
     * @var \ItAces\Repositories\WithJoinsRepository
     */
    protected $withJoins;
    
    public function __construct()
    {
        parent::__construct();
        $this->withJoins = new WithJoinsRepository(true);
    }
    
    /**
    *
    * @param  \Illuminate\Http\Request  $request
    * @return  \Illuminate\Http\JsonResponse
    */
    public function search(Request $request)
    {
        $paginator = $this->cursor($this->withJoins->createQuery($this->class))->appends($request->all());

        return response()->json( new JsonCollectionSerializer($this->withJoins->em(), $paginator), 200);
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
        $instance = $this->withJoins->createOrUpdate($this->class, $data);
        $this->withJoins->em()->flush();
        
        return response()->json( new JsonSerializer($this->withJoins->em(), $instance), 201);
    }
    
    /**
     *
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(int $id)
    {
        $instance = $this->withJoins->findOrFail($this->class, $id);
        
        return response()->json( new JsonSerializer($this->withJoins->em(), $instance), 200);
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
        $instance = $this->withJoins->createOrUpdate($this->class, $data, $id);
        $this->withJoins->em()->flush();
        
        return response()->json( new JsonSerializer($this->withJoins->em(), $instance), 200);
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
     * @return \ItAces\Controllers\JsonCRUDController
     */
    public function setClass(string $class)
    {
        $this->class = $class;
        
        return $this;
    }
    
}
