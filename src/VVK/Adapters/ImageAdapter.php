<?php

namespace VVK\Adapters;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use VVK\Controllers\ApiControllerAdapter;
use VVK\Controllers\WebController;
use VVK\Json\JsonCollectionSerializer;
use VVK\Json\JsonSerializer;
use VVK\Repositories\WithJoinsRepository;
use VVK\Utility\Helper;

class ImageAdapter extends WebController implements ApiControllerAdapter
{
    /**
     *
     * @var string[]
     */
    protected $additional = ['url'];
    
    public function __construct()
    {
        $this->repository = new WithJoinsRepository(true);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \VVK\Controllers\ApiControllerAdapter::search()
     */
    public function search(Request $request, string $classUrlName)
    {
        $className = Helper::classFromUlr($classUrlName);
        $paginator = $this->cursor($this->repository->createQuery($className))->appends($request->all());
        
        return response()->json( new JsonCollectionSerializer($this->repository->em(), $paginator, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \VVK\Controllers\ApiControllerAdapter::read()
     */
    public function read(Request $request, string $classUrlName, int $id)
    {
        $className = Helper::classFromUlr($classUrlName);
        $instance = $this->repository->findOrFail($className, $id);
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \VVK\Controllers\ApiControllerAdapter::create()
     */
    public function create(Request $request, string $classUrlName)
    {
        $className = Helper::classFromUlr($classUrlName);
        $data = $request->json()->all();
        $request->validate($className::getRequestValidationRules());
        $data['name'] = $request->file('image')->getClientOriginalName();
        $data['path'] = $request->file('image')->store(config('itaces.upload.img'));
        
        if (!$data['path']) {
            $e = ValidationException::withMessages([
                'image' => [__('Failed to store file.')],
            ]);
            
            throw $e;
        }
        
        $instance = $this->repository->createOrUpdate($className, $data);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 201);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \VVK\Controllers\ApiControllerAdapter::update()
     */
    public function update(Request $request, string $classUrlName, int $id)
    {
        $className = Helper::classFromUlr($classUrlName);
        $data = $request->json()->all();
        
        if ($request->hasFile('image')) {
            $request->validate($className::getRequestValidationRules());
            $data['name'] = $request->file('image')->getClientOriginalName();
            $data['path'] = $request->file('image')->store(config('itaces.upload.img'));
            
            if (!$data['path']) {
                $e = ValidationException::withMessages([
                    'image' => [__('Failed to store file.')],
                ]);
                
                throw $e;
            }
        }
        
        $instance = $this->repository->createOrUpdate($className, $data, $id);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \VVK\Controllers\ApiControllerAdapter::delete()
     */
    public function delete(Request $request, string $classUrlName, int $id)
    {
        return null;
    }

}
