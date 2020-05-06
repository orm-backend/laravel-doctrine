<?php
namespace ItAces;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use ItAces\ORM\NamingStrategy;
use ItAces\ORM\QuoteStrategy;
use ItAces\Rules\ArrayOfInteger;
use ItAces\Rules\PersistentCollection;
use LaravelDoctrine\ORM\DoctrineManager;
use ItAces\Rules\PersistentFile;

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
        
        include __DIR__.'/../routes.php';
        
        $this->publishes([
            __DIR__.'/../../config/itaces.php' => config_path('itaces.php'),
        ], 'itaces');
    }

}
