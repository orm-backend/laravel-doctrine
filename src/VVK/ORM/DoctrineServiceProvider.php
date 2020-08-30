<?php
namespace VVK\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Contracts\Container\Container;
use LaravelDoctrine\ORM\BootChain;
use LaravelDoctrine\ORM\DoctrineServiceProvider as ServiceProvider;
use LaravelDoctrine\ORM\IlluminateRegistry;

class DoctrineServiceProvider extends ServiceProvider
{
    /**
     * 
     * {@inheritDoc}
     * @see \LaravelDoctrine\ORM\DoctrineServiceProvider::registerManagerRegistry()
     */
    protected function registerManagerRegistry()
    {
        $this->app->singleton('registry', function ($app) {
            $registry = new IlluminateRegistry($app, $app->make(EntityManagerFactory::class));
            
            // Add all managers into the registry
            foreach ($app->make('config')->get('doctrine.managers', []) as $manager => $settings) {
                $registry->addManager($manager, $settings);
            }
            
            return $registry;
        });
            
        // Once the registry get's resolved, we will call the resolve callbacks which were waiting for the registry
        $this->app->afterResolving('registry', function (ManagerRegistry $registry, Container $container) {
            $this->bootExtensionManager();
            
            BootChain::boot($registry);
        });
                
        $this->app->alias('registry', ManagerRegistry::class);
        $this->app->alias('registry', IlluminateRegistry::class);
        
        // This namespace has been deprecated in doctrine/persistence and we have
        // stopped referring to it. Alias is necessary to let other use it until
        // its removed.
        $this->app->alias('registry', \Doctrine\Common\Persistence\ManagerRegistry::class);
    }
    
}