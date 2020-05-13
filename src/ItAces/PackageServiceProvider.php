<?php
namespace ItAces;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ItAces\ACL\AccessControl;
use ItAces\ORM\NamingStrategy;
use ItAces\ORM\QuoteStrategy;
use ItAces\Rules\ArrayOfInteger;
use ItAces\Rules\PersistentCollection;
use ItAces\Rules\PersistentFile;
use LaravelDoctrine\ORM\DoctrineManager;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class PackageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param \LaravelDoctrine\ORM\DoctrineManager $manager
     * 
     * @return void
     */
    public function boot(DoctrineManager $manager)
    {
        $manager->extendAll(function (Configuration $configuration, Connection $connection, EventManager $eventManager) {
            // modify and access settings as is needed
            $configuration->setQuoteStrategy(new QuoteStrategy());
            $configuration->setNamingStrategy(new NamingStrategy());
        });
            
        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('U');
        });

        if (config('app.debug', false)) {
            Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
                if (! str_contains($query->sql, 'oauth')) {
                    Log::info($query->sql, $query->bindings);
                }
            });
        }
        
        Validator::extend('arrayofinteger', ArrayOfInteger::class . '@validate');
        Validator::extend('persistentcollection', PersistentCollection::class . '@validate');
        Validator::extend('persistentfile', PersistentFile::class . '@validate');
        
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
        
        $this->publishes([
            __DIR__.'/../../config/itaces.php' => config_path('itaces.php'),
        ], 'config');
        
        Gate::define('create', 'ItAces\ACL\Policies@isAnyCreatingAllowed');
        Gate::define('read', 'ItAces\ACL\Policies@isAnyReadingAllowed');
        Gate::define('update', 'ItAces\ACL\Policies@isAnyUpdatingAllowed');
        Gate::define('delete', 'ItAces\ACL\Policies@isAnyDeletingAllowed');
        Gate::define('restore', 'ItAces\ACL\Policies@isAnyRestoringAllowed');
        Gate::define('read-record', 'ItAces\ACL\Policies@isReadingAllowed');
        Gate::define('update-record', 'ItAces\ACL\Policies@isUpdatingAllowed');
        Gate::define('delete-record', 'ItAces\ACL\Policies@isDeletingAllowed');
        Gate::define('restore-record', 'ItAces\ACL\Policies@isRestoringAllowed');
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once base_path('vendor') . '/it-aces/laravel-doctrine/src/functions.php';
        
        $this->mergeConfigFrom(
            __DIR__.'/../../config/itaces.php', 'itaces'
        );
        
        $this->app->bind(
            AccessControl::class,
            config('itaces.acl')
        );
        
        $this->app->singleton('acl', function($app) {
            $class = config('itaces.acl');
            return new $class;
        });
    }

}
