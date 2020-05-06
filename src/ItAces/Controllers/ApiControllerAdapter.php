<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use ItAces\UnderAdminControl;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
interface ApiControllerAdapter extends UnderAdminControl
{
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\JsonResponse
     */
    public function search(Request $request);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\JsonResponse
     */
    public function create(Request $request);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, int $id);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer  $id
     * @return  \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id);
    
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, int $id);
    
}
