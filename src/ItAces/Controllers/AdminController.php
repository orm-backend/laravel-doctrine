<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;
use ItAces\View\FieldContainer;
use ItAces\Repositories\WithJoinsRepository;

class AdminController extends WebController
{
    
    /**
     * 
     * @var array
     */
    protected $menu = [];
    
    /**
     *
     * @var \ItAces\Repositories\WithJoinsRepository
     */
    protected $withJoins;

    public function __construct()
    {
        parent::__construct();
        $this->withJoins = new WithJoinsRepository();
        $metadata = $this->repository->em()->getMetadataFactory()->getAllMetadata();

        foreach ($metadata as $classMetadata) {
            /**
             * 
             * @var \Doctrine\ORM\Mapping\ClassMetadata $metadataInfo
             */
            $metadataInfo = $classMetadata;
            
            if ($metadataInfo->isMappedSuperclass) {
                continue;
            }
            
            $className = (new \ReflectionClass($metadataInfo->name))->getShortName();

            $this->menu[] = [
                'name' => __( Str::pluralCamelWords($className) ),
                'link' => route('admin.entity.search', Helper::classToUlr($metadataInfo->name), false) . '/',
                'title' => $metadataInfo->name
            ];
            
            usort($this->menu, function($a, $b) {
                if ($a['name'] == $b['name']) {
                    return 0;
                }
                
                return ($a['name'] < $b['name']) ? -1 : 1;
            });
        }
    }
    
    public function index()
    {
        return view('admin.index', [
            'menu' => $this->menu
        ]);
    }
    
    /**
     * Display a listing of the resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @return \Illuminate\Http\Response
     */
    public function search(Request  $request, string $classUrlName)
    {
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'listingReplacement')) {
            return $adapter::listingReplacement($this->menu, $request);
        }
        
        $classMetadata = $this->repository->em()->getClassMetadata($className);
        $alias = lcfirst($classShortName);
        $container = new FieldContainer($this->repository->em());
        
        $meta = [
            'class' => $className,
            'title' => __( Str::pluralCamelWords($classShortName) ),
            'classUrlName' => $classUrlName
        ];

        $order = $request->get('order');
        $parameters = [];
        
        if (!$order) {
            $parameters = [
                'order' => ['-'.$alias.'.id']
            ];
        }

        $paginator = $this->paginate($this->withJoins->createQuery($className, $parameters, $alias))->appends($request->all());
        $container->buildMetaFields($classMetadata);
        $container->addCollection($paginator->items());

        return view('admin.entity.search', [
            'menu' => $this->menu,
            'paginator' => $paginator,
            'container' => $container,
            'meta' => $meta
        ]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param integer $id
     * @return \Illuminate\Http\Response
     */
    public function details(Request $request, string $classUrlName, int $id)
    {
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'detailsReplacement')) {
            return $adapter::detailsReplacement($this->menu, $id);
        }
        
        $classMetadata = $this->repository->em()->getClassMetadata($className);
        $container = new FieldContainer($this->repository->em());
        
        $meta = [
            'class' => $className,
            'title' => __( Str::pluralCamelWords($classShortName, 1) ),
            'classUrlName' => $classUrlName
        ];
        
        $entity = $this->withJoins->findOrFail($className, $id);
        $container->addEntity($entity);
        
        return view('admin.entity.details', [
            'menu' => $this->menu,
            'container' => $container,
            'meta' => $meta,
            'formAction' => route('admin.entity.update', [$classUrlName, $id])
        ]);
    }
    
    /**
     * Display a listing of the resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param integer $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, string $classUrlName, int $id)
    {
        $className = Helper::classFromUlr($classUrlName);
        $entity = $this->withJoins->findOrFail($className, $id);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'editingReplacement')) {
            return $adapter::editingReplacement($this->menu, $entity);
        }
        
        $classMetadata = $this->repository->em()->getClassMetadata($className);
        $container = new FieldContainer($this->repository->em(), true);
        
        $meta = [
            'class' => $className,
            'title' => __( Str::pluralCamelWords($classShortName, 1) ),
            'classUrlName' => $classUrlName
        ];

        $container->addEntity($entity);
        
        return view('admin.entity.edit', [
            'menu' => $this->menu,
            'container' => $container,
            'meta' => $meta,
            'formAction' => route('admin.entity.update', [$classUrlName, $id])
        ]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, string $classUrlName)
    {
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'creatingReplacement')) {
            return $adapter::creatingReplacement($this->menu);
        }

        $classMetadata = $this->repository->em()->getClassMetadata($className);
        $container = new FieldContainer($this->repository->em(), true);
        $container->buildMetaFields($classMetadata);
        
        $meta = [
            'class' => $className,
            'title' => __( Str::pluralCamelWords($classShortName, 1) ),
            'classUrlName' => $classUrlName
        ];
        
        return view('admin.entity.create', [
            'menu' => $this->menu,
            'container' => $container,
            'meta' => $meta,
            'formAction' => route('admin.entity.store', [$classUrlName])
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @param integer $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $classUrlName, int $id)
    {
        $map = FieldContainer::readRequest($request->post());
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'updatingReplacement')) {
            return $adapter::updatingReplacement($request, $id);
        }
        
        $alias = lcfirst($classShortName);

        try {
            Validator::make($map[$className], $className::getRequestValidationRules())->validate();
            $this->withJoins->createOrUpdate($className, $map[$className], $id);
            $this->withJoins->em()->flush();
        } catch (ValidationException $e) {
            $messages = $e->validator->getMessageBag()->getMessages();
            
            foreach ($messages as $key => $value) {
                $newKey = $classUrlName.'.'.$key;
                $messages[$newKey] = $value;
                unset($messages[$key]);
            }

            throw ValidationException::withMessages($messages);
        }
        
        $url = route('admin.entity.search', $classUrlName);
        
        return redirect($url.'?order[]=-'.$alias.'.updatedAt')->with('success', __('Record updated successfully.'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param string $classUrlName
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, string $classUrlName)
    {
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $adapter = 'App\\Http\\Admin\\Adapters\\'.$classShortName.'Adapter';
        
        if (method_exists($adapter, 'storingReplacement')) {
            return $adapter::storingReplacement($request);
        }
        
        $map = FieldContainer::readRequest($request->post());
        $alias = lcfirst($classShortName);

        try {
            Validator::make($map[$className], $className::getRequestValidationRules())->validate();
            $this->withJoins->createOrUpdate($className, $map[$className]);
            $this->withJoins->em()->flush();
        } catch (ValidationException $e) {
            $messages = $e->validator->getMessageBag()->getMessages();
            
            foreach ($messages as $key => $value) {
                $newKey = $classUrlName.'.'.$key;
                $messages[$newKey] = $value;
                unset($messages[$key]);
            }

            throw ValidationException::withMessages($messages);
        }
        
        $url = route('admin.entity.search', $classUrlName);
        
        return redirect($url.'?order[]=-'.$alias.'.createdAt')->with('success', __('Record created successfully.'));
    }

}
