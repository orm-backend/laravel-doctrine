<?php

namespace OrmBackend\Json;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonSerializable;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class JsonCollectionSerializer implements JsonSerializable
{
    
    /**
     *
     * @var \Illuminate\Pagination\AbstractPaginator;
     */
    protected $paginator;
    
    
    protected $alias;
    
    /**
     *
     * @param \Illuminate\Pagination\AbstractPaginator $paginator
     */
    public function __construct(AbstractPaginator $paginator, string $alias)
    {
        $this->paginator = $paginator;
        $this->alias = $alias;
    }
    
    /**
     *
     * @param \Doctrine\ORM\PersistentCollection $entities
     * @param ClassMetadata $classMetadata
     * @return \stdClass[]
     */
    static public function toJson(PersistentCollection $entities, string $path)
    {
        $data = [];
        
        foreach ($entities->toArray() as $entity) {
            $data[] = JsonSerializer::toJson($entity, $path);
        }
        
        return $data;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $collection = new \stdClass;
        $collection->data = [];
        $paginator = $this->paginator->toArray();
        $collection->links = [
            'path' => $paginator['path'],
            'first_page_url' => $paginator['first_page_url'],
            'prev_page_url' => $paginator['prev_page_url'],
            'next_page_url' => $paginator['next_page_url']
        ];
        
        if ($this->paginator instanceof LengthAwarePaginator) {
            $collection->links['last_page_url'] = $paginator['last_page_url'];
        }
        
        $collection->meta = [
            'current_page' => $paginator['current_page'],
            'per_page' => $paginator['per_page'],
            'from' => $paginator['from'],
            'to' => $paginator['to']
        ];
        
        if ($this->paginator instanceof LengthAwarePaginator) {
            $collection->meta['last_page'] = $paginator['last_page'];
            $collection->meta['total'] = $paginator['total'];
        }

        foreach ($this->paginator->items() as $entity) {
            $collection->data[] = JsonSerializer::toJson($entity, $this->alias);
        }
        
        return $collection;
    }
    
}
