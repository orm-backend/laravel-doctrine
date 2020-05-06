<?php

namespace ItAces\View;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ImageCollectionField extends FileCollectionField
{
    
    protected function getHtmlType()
    {
        return 'image_collection';
    }
}
