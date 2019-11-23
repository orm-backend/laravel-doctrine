<?php

namespace ItAces\Traits;

trait Notifiable
{
    use HasDatabaseNotifications, RoutesNotifications;
    
    public function getKey()
    {
        return $this->getId();
    }

}
