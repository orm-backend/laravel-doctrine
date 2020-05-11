<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use ItAces\Utility\Helper;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ApiController
{
    
    /**
     *
     * @var array
     */
    protected $adapters;
    
    protected $jsonCRUDController;
    
    public function __construct()
    {
        $this->adapters = config('itaces.adapters');
        $this->jsonCRUDController = new JsonCRUDController();
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @return \Illuminate\Http\Response
     */
    public function search(Request  $request, string $classUrlName)
    {
        $class = Helper::classFromUlr($classUrlName);
        $adapterClass = $this->adapters[$class] ?? null;
        
        if ($adapterClass) {
            $adapter = new $adapterClass();
            $response = $adapter->search($request);
            
            if ($response !== null) {
                return $response;
            }
        }
        
        return $this->jsonCRUDController->setClass($class)->search($request);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @return  \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $classUrlName)
    {
        $class = Helper::classFromUlr($classUrlName);
        $adapterClass = $this->adapters[$class] ?? null;
        
        if ($adapterClass) {
            $adapter = new $adapterClass();
            $response = $adapter->create($request);
            
            if ($response !== null) {
                return $response;
            }
        }
        
        return $this->jsonCRUDController->setClass($class)->create($request);
    }
    
    /**
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, string $classUrlName, int $id)
    {
        $class = Helper::classFromUlr($classUrlName);
        $adapterClass = $this->adapters[$class] ?? null;
        
        if ($adapterClass) {
            $adapter = new $adapterClass();
            $response = $adapter->read($request, $id);
            
            if ($response !== null) {
                return $response;
            }
        }
        
        return $this->jsonCRUDController->setClass($class)->read($id);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $classUrlName, int $id)
    {
        $class = Helper::classFromUlr($classUrlName);
        $adapterClass = $this->adapters[$class] ?? null;
        
        if ($adapterClass) {
            $adapter = new $adapterClass();
            $response = $adapter->update($request, $id);
            
            if ($response !== null) {
                return $response;
            }
        }
        
        return $this->jsonCRUDController->setClass($class)->update($request, $id);
    }
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param  integer  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, string $classUrlName, int $id)
    {
        $class = Helper::classFromUlr($classUrlName);
        $adapterClass = $this->adapters[$class] ?? null;
        
        if ($adapterClass) {
            $adapter = new $adapterClass();
            $response = $adapter->delete($request, $id);
            
            if ($response !== null) {
                return $response;
            }
        }
        
        return $this->jsonCRUDController->setClass($class)->delete($id);
    }

}
