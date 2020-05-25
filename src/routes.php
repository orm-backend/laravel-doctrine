<?php
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'img',
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
        Route::get('/{model}', 'ApiController@search')->middleware('can:read,model');
        Route::post('/{model}/search', 'ApiController@search')->middleware('can:read,model');
        Route::post('/{model}/create', 'ApiController@create')->middleware('can:create,model');
        Route::get('/{model}/read/{id}', 'ApiController@read')->middleware('can:read,model');
        Route::put('/{model}/update/{id}', 'ApiController@update')->middleware('can:update,model');
        Route::delete('/{model}/delete/{id}', 'ApiController@delete')->middleware('can:delete,model');
    });
});
