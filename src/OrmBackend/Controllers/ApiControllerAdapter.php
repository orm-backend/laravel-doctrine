<?php

namespace OrmBackend\Controllers;

use Illuminate\Http\Request;
use OrmBackend\Publishable;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
interface ApiControllerAdapter extends Publishable
{
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $classUrlName
     * @return  \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, string $classUrlName);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $classUrlName
     * @return  \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $classUrlName);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $classUrlName
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, string $classUrlName, int $id);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $classUrlName
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $classUrlName, int $id);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $classUrlName
     * @param  integer  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, string $classUrlName, int $id);
    
}
