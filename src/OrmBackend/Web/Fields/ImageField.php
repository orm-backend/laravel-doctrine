<?php
namespace OrmBackend\Web\Fields;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ImageField extends FileField
{
    
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\Web\Fields\MetaField::getHtmlType()
     */
    protected function getHtmlType()
    {
        return 'image';
    }

}
