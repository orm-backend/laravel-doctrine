<?php

namespace VVK\Json;

use JsonSerializable;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DatatableSerializer extends JsonCollectionSerializer
{

    /**
     * 
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        $collection = new \stdClass;
        $collection->meta = new \stdClass;
        $collection->meta->page = $this->paginator['current_page'];
        $collection->meta->pages = $this->paginator['last_page'];
        $collection->meta->perpage = $this->paginator['per_page'];
        $collection->meta->total = $this->paginator['total'];
        $collection->data = [];
        
        foreach ($this->entities as $entity) {
            $serializer = new JsonSerializer($this->em, $entity, $this->additional);
            $collection->data[] = $serializer->jsonSerialize();
        }
        
        return $collection;
    }
    
}
