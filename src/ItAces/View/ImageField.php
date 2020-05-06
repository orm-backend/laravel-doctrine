<?php

namespace ItAces\View;

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
     * @see \ItAces\View\MetaField::getHtmlType()
     */
    protected function getHtmlType()
    {
        return 'image';
    }

}
