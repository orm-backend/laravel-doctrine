<?php
namespace OrmBackend;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OrmBackend\ACL\AccessControl;
use OrmBackend\DBAL\Types\CarbonDate;
use OrmBackend\DBAL\Types\CarbonDateTime;
use OrmBackend\Filters\SoftDeleteFilter;
use OrmBackend\Listener\DoctrineListener;
use OrmBackend\ORM\NamingStrategy;
use OrmBackend\ORM\QuoteStrategy;
use OrmBackend\Rules\ArrayOfInteger;
use OrmBackend\Rules\PersistentCollection;
use OrmBackend\Rules\PersistentFile;
use OrmBackend\DBAL\Types\CarbonTime;

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
                base_path('vendor/vvk/laravel-doctrine/src/OrmBackend/ORM/Entities') => 'OrmBackend\ORM\Entities'
            ],
            'OrmBackend\ORM\Entities'
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
        
        Gate::define('create', 'OrmBackend\ACL\Policies@isAnyCreatingAllowed');
        Gate::define('read', 'OrmBackend\ACL\Policies@isAnyReadingAllowed');
        Gate::define('update', 'OrmBackend\ACL\Policies@isAnyUpdatingAllowed');
        Gate::define('delete', 'OrmBackend\ACL\Policies@isAnyDeletingAllowed');
        Gate::define('restore', 'OrmBackend\ACL\Policies@isAnyRestoringAllowed');
        Gate::define('read-record', 'OrmBackend\ACL\Policies@isReadingAllowed');
        Gate::define('update-record', 'OrmBackend\ACL\Policies@isUpdatingAllowed');
        Gate::define('delete-record', 'OrmBackend\ACL\Policies@isDeletingAllowed');
        Gate::define('restore-record', 'OrmBackend\ACL\Policies@isRestoringAllowed');
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require_once base_path('vendor') . '/vvk/laravel-doctrine/src/functions.php';
        
        $this->mergeConfigFrom(
            __DIR__.'/../../config/itaces.php', 'itaces'
        );
        
        $this->addType(Types::TIME_MUTABLE, CarbonTime::class);
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
