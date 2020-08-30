<?php

namespace VVK\Traits;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        //return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
        return [];
    }
    
    /**
     * Get the entity's read notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function readNotifications()
    {
        //return $this->notifications()->whereNotNull('read_at');
        return [];
    }
    
    /**
     * Get the entity's unread notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function unreadNotifications()
    {
        //return $this->notifications()->whereNull('read_at');
        return [];
    }
}
