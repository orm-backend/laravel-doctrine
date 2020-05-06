<?php

namespace ItAces\View;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class FileCollectionField extends CollectionField
{
    
    protected function getHtmlType()
    {
        return 'file_collection';
    }
}
