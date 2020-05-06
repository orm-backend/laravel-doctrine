<?php
namespace ItAces;

use Illuminate\Support\Facades\Route;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ItAces
{

    /**
     * 
     * @param \LaravelDoctrine\ORM\DoctrineManager $manager
     */
//     public static function boot(DoctrineManager $manager)
//     {
//         $manager->extendAll(function (Configuration $configuration, Connection $connection, EventManager $eventManager) {
//             // modify and access settings as is needed
//             $configuration->setQuoteStrategy(new QuoteStrategy());
//             $configuration->setNamingStrategy(new NamingStrategy());
//         });
        
//         Carbon::serializeUsing(function ($carbon) {
//             return $carbon->format('U');
//         });
        
//         if (config('app.debug', false)) {
//             Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
//                 if (! str_contains($query->sql, 'oauth')) {
//                     Log::info($query->sql, $query->bindings);
//                 }
//             });
//         }
        
//         Validator::extend('arrayofinteger', ArrayOfInteger::class . '@validate');
//         Validator::extend('persistentcollection', PersistentCollection::class . '@validate');
//         Validator::extend('persistentfile', PersistentFile::class . '@validate');
//     }
    
    /**
     * 
     * @param array $middleware
     */
//     public static function webRoutes(array $middleware = [])
//     {   
//         Route::group(array(
//             'prefix' => 'image',
//             'namespace' => '\ItAces\Controllers'
//         ), function () {
//             Route::get('/{mode}/{width}/{height}', 'ImageController@resize')->name('image.resize');
//         });
//     }
    
    /**
     *
     * @param array $middleware
     */
    public static function apiRoutes(array $middleware = [])
    {
        Route::group(array(
            'prefix' => 'entities',
            'namespace' => '\ItAces\Controllers',
            'middleware' => $middleware
        ), function () {
            Route::get('/{name}', 'ApiController@search');
            Route::post('/{name}/search', 'ApiController@search');
            Route::post('/{name}/create', 'ApiController@create');
            Route::get('/{name}/read/{id}', 'ApiController@read');
            Route::put('/{name}/update/{id}', 'ApiController@update');
            Route::delete('/{name}/delete/{id}', 'ApiController@delete');
        });
    }

}
