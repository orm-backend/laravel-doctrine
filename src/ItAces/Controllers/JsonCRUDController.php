<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use ItAces\Json\JsonCollectionSerializer;
use ItAces\Json\JsonSerializer;

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
     * @var string[]
     */
    protected $additional = [];
    
    /**
    * Display a listing of the resource.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return  \Illuminate\Http\JsonResponse
    */
    public function search(Request $request)
    {
        $paginator = $this->paginate($this->repository->createQuery($this->class))->appends($request->all());
        
        return response()->json( new JsonCollectionSerializer($this->repository->em(), $paginator, $this->additional), 200);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->json();
        $request->validate($this->class::getRequestValidationRules());
        $instance = $this->repository->createOrUpdate($this->class, $data);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 201);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(int $id)
    {
        $instance = $this->repository->findOrFail($this->class, $id);
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $data = $request->json();
        $request->validate($this->class::getRequestValidationRules());
        $instance = $this->repository->createOrUpdate($this->class, $data, $id);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }
    
    /**
     * Remove the specified resource from storage.
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
    
}
