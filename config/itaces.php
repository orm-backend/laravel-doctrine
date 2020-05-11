<?php

return [
    
    'upload' => [
        'img' => 'upload/img',
        'doc' => 'upload/doc',
        'cache' => 'img'
    ],
    
    'adapters' => [
        'api' => [
            App\Model\Image::class => ItAces\Adapters\ImageAdapter::class,
        ],
    ],
    
];
