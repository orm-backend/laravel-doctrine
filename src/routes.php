<?php
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'image',
    'namespace' => '\ItAces\Controllers'
], function () {
    Route::get('/{mode}/{width}/{height}', 'ImageController@resize')->name('image.resize');
});

Route::group([
    'prefix' => 'api',
    'namespace' => '\ItAces\Controllers',
    'middleware' => [
        'throttle:60,1',
        'bindings',
        //'auth:api'
    ]
], function () {
    Route::group([
        'prefix' => 'entities'
    ], function () {
        Route::get('/{name}', 'ApiController@search')->middleware('can:read,name');
        Route::post('/{name}/search', 'ApiController@search')->middleware('can:read,name');
        Route::post('/{name}/create', 'ApiController@create')->middleware('can:create,name');
        Route::get('/{name}/read/{id}', 'ApiController@read')->middleware('can:read,name');
        Route::put('/{name}/update/{id}', 'ApiController@update')->middleware('can:update,name');
        Route::delete('/{name}/delete/{id}', 'ApiController@delete')->middleware('can:delete,name');
    });
});
