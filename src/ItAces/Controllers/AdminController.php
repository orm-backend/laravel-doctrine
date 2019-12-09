<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ItAces\Utility\Helper;
use ItAces\Utility\Str;
use ItAces\View\FieldContainer;
use ItAces\Repositories\WithJoinsRepository;

class AdminController extends WebController
{
    
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
    public function edit(Request $request, string $classUrlName, int $id)
    {
        $className = Helper::classFromUlr($classUrlName);
        $classShortName = (new \ReflectionClass($className))->getShortName();
        $alias = lcfirst($classShortName);
        $classMetadata = $this->repository->em()->getClassMetadata($className);
        $container = new FieldContainer($this->repository->em());
        
        $meta = [
            'class' => $className,
            'title' => __( Str::pluralCamelWords($classShortName, 1) ),
            'classUrlName' => $classUrlName
        ];
        
        $entity = $this->withJoins->findOrFail($className, $id);
        $container->addEntity($entity);
        
        return view('admin.entity.edit', [
            'menu' => $this->menu,
            'container' => $container,
            'meta' => $meta,
            'formAction' => route('admin.entity.update', [$classUrlName, $id])
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
        $request->validate($className::getRequestValidationRules());
        $this->withJoins->createOrUpdate($className, $map[$className], $id);
        
//         foreach ($map as $className => $data) {
//             $request->validate($className::getRequestValidationRules());
//             $this->withJoins->createOrUpdate($className, $data, $id);
//         }

        try {
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
        
        return redirect()->route('admin.entity.search', $classUrlName)->with('success', __('Entity updated successfully.'));
    }

}
