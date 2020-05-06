<?php
use Illuminate\Support\Facades\Route;

Route::group(array(
    'prefix' => 'image',
    'namespace' => '\ItAces\Controllers'
), function () {
    Route::get('/{mode}/{width}/{height}', 'ImageController@resize')->name('image.resize');
});
