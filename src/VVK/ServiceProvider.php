<?php
namespace VVK;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
abstract class ServiceProvider extends ServiceProviderBase
{
    
    protected function bootModel(EntityManagerInterface $manager, array $prefixes, string $namespace)
    {
        /**
         *
         * @var \Doctrine\Persistence\Mapping\Driver\MappingDriverChain $driverChain
         */
        $driverChain = $manager->getConfiguration()->getMetadataDriverImpl();
        
        /**
         * If the application uses the same driver as this package, we do not need to create a new one,
         * and we proceed to configure the model path and namespace for default driver.
         *
         * @var \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver $driver
         */
        $driver = $driverChain->getDefaultDriver();
        
        if ($driver instanceof SimplifiedXmlDriver) {
            /**
             * 
             * @var \Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator $locator
             */
            $locator = $driver->getLocator();
            $locator->addNamespacePrefixes($prefixes);
        } else {
            $driver = new SimplifiedXmlDriver($prefixes);
            $driverChain->addDriver($driver, $namespace);
        }
        
        $driver->setGlobalBasename('global'); // I'm going to use global.orm.xml
    }
    
}
