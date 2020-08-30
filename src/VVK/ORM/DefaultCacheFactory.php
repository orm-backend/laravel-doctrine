<?php
namespace VVK\ORM;

use Doctrine\ORM\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Cache\DefaultCacheFactory as CacheFactory;
use Doctrine\ORM\Mapping\ClassMetadata;

class DefaultCacheFactory extends CacheFactory
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \Doctrine\ORM\Cache\DefaultCacheFactory::buildQueryCache()
     */
    public function buildQueryCache(EntityManagerInterface $em, $regionName = null)
    {
        return new DefaultQueryCache(
            $em,
            $this->getRegion(
                [
                    'region' => $regionName ?: Cache::DEFAULT_QUERY_REGION_NAME,
                    'usage'  => ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE
                ]
            )
        );
    }
    
}