<?php

namespace ItAces\Traits;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
trait Notifiable
{
    use HasDatabaseNotifications, RoutesNotifications;
    
    public function getKey()
    {
        return $this->getId();
    }

}
