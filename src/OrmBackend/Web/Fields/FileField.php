<?php
namespace OrmBackend\Web\Fields;

use Doctrine\ORM\Mapping\ClassMetadata;
use Illuminate\Support\Facades\Storage;
use OrmBackend\ORM\Entities\Entity;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class FileField extends ReferenceField
{
    
    /**
     * 
     * @var string
     */
    public $url;
    
    /**
     *
     * @var string
     */
    public $path;
    
    /**
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param string $fieldName
     * @param \OrmBackend\ORM\Entities\Entity $entity
     * @param int $index
     * @return \OrmBackend\Web\Fields\MetaField
     */
    public static function getInstance(ClassMetadata $classMetadata, string $fieldName, Entity $entity = null, int $index = null)
    {
        $instance = parent::getInstance($classMetadata, $fieldName, $entity, $index);

        if ($entity && array_search($fieldName, FieldContainer::FORBIDDEN_FIELDS) === false) {
            /**
             * 
             * @var \OrmBackend\Types\FileType $file
             */
            $file = $entity->{$fieldName};

            if ($file) {
                $instance->path = $file->getPath();
                $instance->url = Storage::url($instance->path);
            }
        }
        
        return $instance;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\Web\Fields\MetaField::getHtmlType()
     */
    protected function getHtmlType()
    {
        return 'file';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\Web\Fields\MetaField::getDefaultSortable()
     */
    protected function getDefaultSortable()
    {
        return 'false';
    }

}
