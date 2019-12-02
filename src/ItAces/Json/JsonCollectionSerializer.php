<?php

namespace ItAces\Json;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
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
     * @var \ItAces\ORM\Entities\EntityBase[]
     */
    protected $entities;
    
    /**
     * 
     * @var string[]
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
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string[] $additional
     */
    public function __construct(EntityManager $em, LengthAwarePaginator $paginator, array $additional = [])
    {
        $this->em = $em;
        $this->entities = $paginator->items();
        $this->paginator = $paginator->toArray();
        $this->additional = $additional;
    }
    
    /**
     * 
     * @param array $entities
     * @param ClassMetadata $classMetadata
     * @return \stdClass[]
     */
    static public function toJson(array $entities, ClassMetadata $classMetadata)
    {
        $data = [];

        foreach ($entities as $entity) {
            $serializer = new JsonSerializer($this->em, $entity);
            $data[] = $serializer->toJson();
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
            'prev_page_url' => $this->paginator['prev_page_url'],
            'next_page_url' => $this->paginator['next_page_url'],
            'first_page_url' => $this->paginator['first_page_url'],
            'last_page_url' => $this->paginator['last_page_url'],
            'next_page_url' => $this->paginator['next_page_url']
        ];
        
        $collection->meta = [
            'current_page' => $this->paginator['current_page'],
            'last_page' => $this->paginator['last_page'],
            'per_page' => $this->paginator['per_page'],
            'from' => $this->paginator['from'],
            'to' => $this->paginator['to'],
            'total' => $this->paginator['total']
        ];
        
        foreach ($this->entities as $entity) {
            $serializer = new JsonSerializer($this->em, $entity, $this->additional);
            $collection->data[] = $serializer->jsonSerialize();
        }
        
        return $collection;
    }
    
}
