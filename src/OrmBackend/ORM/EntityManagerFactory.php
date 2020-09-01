<?php
namespace OrmBackend\ORM;

use Doctrine\ORM\Configuration;
use LaravelDoctrine\ORM\EntityManagerFactory as ManagerFactory;

class EntityManagerFactory extends ManagerFactory
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \LaravelDoctrine\ORM\EntityManagerFactory::setSecondLevelCaching()
     */
    protected function setSecondLevelCaching(Configuration $configuration)
    {
        if ($this->config->get('doctrine.cache.second_level', false)) {
            $configuration->setSecondLevelCacheEnabled(true);
            
            $cacheConfig = $configuration->getSecondLevelCacheConfiguration();
            $cacheConfig->getRegionsConfiguration()->setDefaultLifetime( config('ormbackend.caches.second_ttl', 3600) );
            $cacheConfig->setCacheFactory(
                new DefaultCacheFactory(
                    $cacheConfig->getRegionsConfiguration(),
                    $this->cache->driver()
                )
            );
        }
    }
    
}