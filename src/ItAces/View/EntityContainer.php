<?php
namespace ItAces\View;

use ItAces\ORM\Entities\EntityBase;
use ItAces\ORM\DevelopmentException;
use ItAces\Utility\Helper;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class EntityContainer extends FieldContainer
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\FieldContainer::addEntity()
     */
    public function addEntity(EntityBase $entity)
    {
        throw new DevelopmentException('Method unsupported. Use the addCollection method or the FieldContainer class instead.');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\FieldContainer::addCollection()
     */
    public function addCollection(array $data)
    {
        foreach ($data as $index => $entity) {
            $className = get_class($entity);
            $classMetadata = $this->em->getClassMetadata($className);
            $this->entities[$index] = $this->wrapEntity($classMetadata, $entity, $index);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ItAces\View\FieldContainer::readRequest()
     */
    public static function readRequest(array $request, array &$storedFiles = null) : array
    {
        $map = [];

        foreach ($request as $classUrlName => $data) {
            if (!is_array($data)) {
                continue;
            }
            
            $className = Helper::classFromUlr($classUrlName);
            
            if (!array_key_exists($className, $map)) {
                $map[$className] = [];
            }
            
            foreach ($data as $value) {
                $map[$className][] = self::readEntity($className, $value, $storedFiles);
            }
        }

        return $map;
    }
    
}
