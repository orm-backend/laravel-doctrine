<?php
use Illuminate\Support\Facades\Route;

Route::pattern('model', '[a-z_\-]+');

Route::group([
    'prefix' => 'img',
    'namespace' => '\VVK\Controllers'
], function () {
    Route::get('/{mode}/{width}/{height}', 'ImageController@resize')->name('image.resize')
        ->where(['mode' => '(zoom|center|simple|feel)', 'width' => '[0-9]+', 'height' => '[0-9]+']);
});

Route::group([
    'prefix' => 'api',
    'namespace' => '\VVK\Controllers',
    'middleware' => [
        'api',
        'auth:api'
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
