<?php

namespace ItAces\Adapters;

use App\Model\Image;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ItAces\Controllers\ApiControllerAdapter;
use ItAces\Controllers\WebController;
use ItAces\Json\JsonCollectionSerializer;
use ItAces\Json\JsonSerializer;
use ItAces\Repositories\WithJoinsRepository;

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
     * @see \ItAces\Controllers\ApiControllerAdapter::search()
     */
    public function search(Request $request)
    {
        $paginator = $this->cursor($this->repository->createQuery(Image::class))->appends($request->all());
        
        return response()->json( new JsonCollectionSerializer($this->repository->em(), $paginator, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Controllers\ApiControllerAdapter::read()
     */
    public function read(Request $request, int $id)
    {
        $instance = $this->repository->findOrFail(Image::class, $id);
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Controllers\ApiControllerAdapter::create()
     */
    public function create(Request $request)
    {
        $data = $request->json()->all();
        $request->validate(Image::getRequestValidationRules());
        $data['name'] = $request->file('image')->getClientOriginalName();
        $data['path'] = $request->file('image')->store('images/originals');
        
        if (!$data['path']) {
            $e = ValidationException::withMessages([
                'image' => [__('Failed to store file.')],
            ]);
            
            throw $e;
        }
        
        $instance = $this->repository->createOrUpdate(Image::class, $data);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 201);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Controllers\ApiControllerAdapter::update()
     */
    public function update(Request $request, int $id)
    {
        $data = $request->json()->all();
        
        if ($request->hasFile('image')) {
            $request->validate(Image::getRequestValidationRules());
            $data['name'] = $request->file('image')->getClientOriginalName();
            $data['path'] = $request->file('image')->store('images/originals');
            
            if (!$data['path']) {
                $e = ValidationException::withMessages([
                    'image' => [__('Failed to store file.')],
                ]);
                
                throw $e;
            }
        }
        
        $instance = $this->repository->createOrUpdate(Image::class, $data, $id);
        $this->repository->em()->flush();
        
        return response()->json( new JsonSerializer($this->repository->em(), $instance, $this->additional), 200);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\Controllers\ApiControllerAdapter::delete()
     */
    public function delete(Request $request, int $id)
    {
        return null;
    }

}
