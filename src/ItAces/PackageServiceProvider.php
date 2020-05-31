<?php
namespace ItAces;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use ItAces\ACL\AccessControl;
use ItAces\DBAL\Types\CarbonDate;
use ItAces\DBAL\Types\CarbonDateTime;
use ItAces\Filters\SoftDeleteFilter;
use ItAces\Listener\DoctrineListener;
use ItAces\ORM\DoctrineServiceProvider;
use ItAces\ORM\NamingStrategy;
use ItAces\ORM\QuoteStrategy;
use ItAces\Rules\ArrayOfInteger;
use ItAces\Rules\PersistentCollection;
use ItAces\Rules\PersistentFile;

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
     * @param \Doctrine\ORM\EntityManagerInterface $manager
     * 
     * @return void
     */
    public function boot(EntityManagerInterface $manager)
    {
        $manager->getConfiguration()->setQuoteStrategy(new QuoteStrategy());
        $manager->getConfiguration()->setNamingStrategy(new NamingStrategy());
        
        if (config('itaces.softdelete', true)) {
            $manager->getConfiguration()->addFilter('softdelete', SoftDeleteFilter::class);
            $manager->getFilters()->enable('softdelete');
            $manager->getEventManager()->addEventListener(Events::preFlush, app()->make(DoctrineListener::class));
        }
        
        $this->bootModel(
            $manager,
            [
                base_path('vendor/it-aces/laravel-doctrine/src/ItAces/ORM/Entities') => 'ItAces\ORM\Entities'
            ],
            'ItAces\ORM\Entities'
        );
        
        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('U');
        });
        
        Validator::extend('arrayofinteger', ArrayOfInteger::class . '@validate');
        Validator::extend('persistentcollection', PersistentCollection::class . '@validate');
        Validator::extend('persistentfile', PersistentFile::class . '@validate');
        
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
        
        $this->publishes([
            __DIR__.'/../../config/itaces.php' => config_path('itaces.php'),
        ], 'itaces-config');
        
        $this->publishes([
            __DIR__.'/../../app/Model' => app_path('Model'),
        ], 'itaces-model');
        
//         $this->publishes([
//             __DIR__.'/../../database/seeds' => database_path('seeds'),
//         ], 'itaces-seeds');
        
        Gate::guessPolicyNamesUsing(function ($modelClass) {
            // Turn Off Policy Auto-Discovery
        });
        
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
        
        $this->addType(Types::DATE_MUTABLE, CarbonDate::class);
        $this->addType(Types::DATETIME_MUTABLE, CarbonDateTime::class);
        $this->addType(Types::DATETIMETZ_MUTABLE, CarbonDateTime::class);
        
        $this->app->singleton(AccessControl::class, config('itaces.acl'));
        $this->app->alias(AccessControl::class, 'acl');
    }
    
    /**
     * @param $name
     * @param $class
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function addType($name, $class)
    {
        if (!Type::hasType($name)) {
            Type::addType($name, $class);
        } else {
            Type::overrideType($name, $class);
        }
    }

}
