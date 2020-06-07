<?php

namespace ItAces\Json;

use Doctrine\ORM\EntityManager;
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
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * 
     * @var \ItAces\ORM\Entities\Entity[]
     */
    protected $entities;
    
    /**
     * 
     * @var \Illuminate\Pagination\AbstractPaginator;
     */
    protected $paginator;
    
    /**
     * 
     * @var string[]
     */
    protected $additional;
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Illuminate\Pagination\AbstractPaginator $paginator
     * @param string[] $additional
     */
    public function __construct(EntityManager $em, AbstractPaginator $paginator, array $additional = [])
    {
        $this->em = $em;
        $this->entities = $paginator->items();
        $this->paginator = $paginator->toArray();
        $this->additional = $additional;
    }
    
    /**
     * 
     * @param \Doctrine\ORM\PersistentCollection $entities
     * @param ClassMetadata $classMetadata
     * @return \stdClass[]
     */
    static public function toJson(PersistentCollection $entities, ClassMetadata $classMetadata)
    {
        $data = [];

        foreach ($entities as $entity) {
            $data[] = JsonSerializer::toJson($entity, $classMetadata);
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
        $collection->links = [
            'path' => $this->paginator['path'],
            'first_page_url' => $this->paginator['first_page_url'],
            'prev_page_url' => $this->paginator['prev_page_url'],
            'next_page_url' => $this->paginator['next_page_url']
        ];
        
        if ($this->paginator instanceof LengthAwarePaginator) {
            $collection->links['last_page_url'] = $this->paginator['last_page_url'];
        }
        
        $collection->meta = [
            'current_page' => $this->paginator['current_page'],
            'per_page' => $this->paginator['per_page'],
            'from' => $this->paginator['from'],
            'to' => $this->paginator['to']
        ];
        
        if ($this->paginator instanceof LengthAwarePaginator) {
            $collection->meta['last_page'] = $this->paginator['last_page'];
            $collection->meta['total'] = $this->paginator['total'];
        }
        
        foreach ($this->entities as $entity) {
            $serializer = new JsonSerializer($this->em, $entity, $this->additional);
            $collection->data[] = $serializer->jsonSerialize();
        }
        
        return $collection;
    }
    
}
