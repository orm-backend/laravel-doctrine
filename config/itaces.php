<?php

return [
    
    'upload' => [
        'img' => 'upload/img',
        'doc' => 'upload/doc',
        'cache' => 'img'
    ],
    
    'caches' => [
        'enabled' => true,
        'query_ttl' => env('DOCTRINE_QUERY_CACHE_TTL', 3600),
        'second_ttl' => env('DOCTRINE_SECOND_CACHE_TTL', 300),
        'result_ttl' => env('DOCTRINE_RESULT_CACHE_TTL', 300),
    ],
    
    'adapters' => [
        App\Model\Image::class => ItAces\Adapters\ImageAdapter::class,
    ],
    
    'acl' => ItAces\ACL\DefaultImplementation::class,
    
    'softdelete' => true,

    'perms' => [
        'forbidden' => 1,
        'guest' => [
            'create' => 2,
            'read' => 4,
            'update' => 8,
            'delete' => 16,
        ],
        'entity' => [
            'create' => 32,
            'read' => 64,
            'update' => 128,
            'delete' => 256,
            'restore' => 512
        ],
        'record' => [
            'read' => 1024,
            'update' => 2048,
            'delete' => 4096,
            'restore' => 8192
        ],
        'full' => 16382
    ]
    
];