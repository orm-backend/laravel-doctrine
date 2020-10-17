<?php
use Illuminate\Support\Facades\Route;

Route::pattern('model', '[a-z_\-]+');

Route::group([
    'prefix' => 'img',
    'namespace' => '\OrmBackend\Controllers'
], function () {
    Route::get('/{mode}/{width}/{height}', 'ImageController@resize')->name('image.resize')
        ->where(['mode' => '(zoom|center|simple|feel)', 'width' => '[0-9]+', 'height' => '[0-9]+']);
});

$middleware = ['api'];

if (config('ormbackend.api_use_oauth', false)) {
    $middleware[] = 'auth:api';
}

Route::group([
    'prefix' => 'api',
    'namespace' => '\OrmBackend\Controllers',
    'middleware' => $middleware
], function () {
    Route::group([
        'prefix' => 'entities'
    ], function () {
        // search
        Route::get('/{model}', 'ApiController@search')->middleware('can:read,model');
        Route::post('/{model}', 'ApiController@search')->middleware('can:read,model');
        Route::put('/{model}', 'ApiController@search')->middleware('can:read,model');
        // crud
        Route::post('/{model}/crud', 'ApiController@create')->middleware('can:create,model');
        Route::get('/{model}/crud/{id}', 'ApiController@read')->middleware('can:read,model');
        Route::put('/{model}/crud/{id}', 'ApiController@update')->middleware('can:update,model');
        Route::delete('/{model}/crud/{id}', 'ApiController@delete')->middleware('can:delete,model');
    });
});
